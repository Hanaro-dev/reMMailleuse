<?php
/**
 * ===== GESTIONNAIRE DE CACHE - SITE REMMAILLEUSE =====
 * Système de cache unifié pour améliorer les performances
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class CacheManager {
    private $cacheDir;
    private $defaultTTL;
    private $maxCacheSize;
    private $compressionEnabled;
    
    public function __construct($config = []) {
        $this->cacheDir = $config['cache_dir'] ?? dirname(__DIR__) . '/cache/';
        $this->defaultTTL = $config['default_ttl'] ?? 3600; // 1 heure
        $this->maxCacheSize = $config['max_cache_size'] ?? 100 * 1024 * 1024; // 100MB
        $this->compressionEnabled = $config['compression'] ?? true;
        
        $this->initializeCacheDir();
    }
    
    /**
     * Initialise le répertoire de cache
     */
    private function initializeCacheDir() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Créer les sous-dossiers
        $subDirs = ['api', 'images', 'static', 'data'];
        foreach ($subDirs as $dir) {
            $fullPath = $this->cacheDir . $dir . '/';
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }
    
    /**
     * Génère une clé de cache valide
     */
    private function generateCacheKey($key) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
    
    /**
     * Génère le chemin complet du fichier de cache
     */
    private function getCacheFilePath($key, $category = 'api') {
        $safeKey = $this->generateCacheKey($key);
        return $this->cacheDir . $category . '/' . $safeKey . '.cache';
    }
    
    /**
     * Stocke une valeur dans le cache
     */
    public function set($key, $value, $ttl = null, $category = 'api') {
        $ttl = $ttl ?? $this->defaultTTL;
        $filePath = $this->getCacheFilePath($key, $category);
        
        $cacheData = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time(),
            'key' => $key
        ];
        
        try {
            // Sérialiser les données
            $serializedData = serialize($cacheData);
            
            // Compression optionnelle
            if ($this->compressionEnabled && function_exists('gzcompress')) {
                $serializedData = gzcompress($serializedData, 6);
            }
            
            // Écrire dans le fichier avec verrou
            $result = file_put_contents($filePath, $serializedData, LOCK_EX);
            
            if ($result === false) {
                throw new Exception("Impossible d'écrire dans le cache: $filePath");
            }
            
            // Vérifier la taille du cache
            $this->checkCacheSize($category);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur cache set: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère une valeur du cache
     */
    public function get($key, $category = 'api') {
        $filePath = $this->getCacheFilePath($key, $category);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        try {
            $data = file_get_contents($filePath);
            
            if ($data === false) {
                return null;
            }
            
            // Décompression si nécessaire
            if ($this->compressionEnabled && function_exists('gzuncompress')) {
                $uncompressed = @gzuncompress($data);
                if ($uncompressed !== false) {
                    $data = $uncompressed;
                }
            }
            
            $cacheData = unserialize($data);
            
            if ($cacheData === false) {
                // Fichier corrompu, le supprimer
                unlink($filePath);
                return null;
            }
            
            // Vérifier l'expiration
            if (time() > $cacheData['expires']) {
                unlink($filePath);
                return null;
            }
            
            return $cacheData['value'];
            
        } catch (Exception $e) {
            error_log("Erreur cache get: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifie si une clé existe dans le cache
     */
    public function has($key, $category = 'api') {
        return $this->get($key, $category) !== null;
    }
    
    /**
     * Supprime une entrée du cache
     */
    public function delete($key, $category = 'api') {
        $filePath = $this->getCacheFilePath($key, $category);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Vide tout le cache d'une catégorie
     */
    public function clearCategory($category = 'api') {
        $categoryDir = $this->cacheDir . $category . '/';
        
        if (!is_dir($categoryDir)) {
            return true;
        }
        
        $files = glob($categoryDir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Vide tout le cache
     */
    public function clear() {
        $subDirs = ['api', 'images', 'static', 'data'];
        $totalCleared = 0;
        
        foreach ($subDirs as $dir) {
            $totalCleared += $this->clearCategory($dir);
        }
        
        return $totalCleared;
    }
    
    /**
     * Nettoie les entrées expirées
     */
    public function cleanExpired() {
        $subDirs = ['api', 'images', 'static', 'data'];
        $cleaned = 0;
        
        foreach ($subDirs as $category) {
            $categoryDir = $this->cacheDir . $category . '/';
            
            if (!is_dir($categoryDir)) {
                continue;
            }
            
            $files = glob($categoryDir . '*.cache');
            
            foreach ($files as $file) {
                try {
                    $data = file_get_contents($file);
                    
                    if ($data === false) {
                        continue;
                    }
                    
                    // Décompression si nécessaire
                    if ($this->compressionEnabled && function_exists('gzuncompress')) {
                        $uncompressed = @gzuncompress($data);
                        if ($uncompressed !== false) {
                            $data = $uncompressed;
                        }
                    }
                    
                    $cacheData = unserialize($data);
                    
                    if ($cacheData === false || time() > $cacheData['expires']) {
                        unlink($file);
                        $cleaned++;
                    }
                    
                } catch (Exception $e) {
                    // Fichier corrompu, le supprimer
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Vérifie la taille du cache et nettoie si nécessaire
     */
    private function checkCacheSize($category = null) {
        $totalSize = 0;
        $files = [];
        
        if ($category) {
            $categoryDir = $this->cacheDir . $category . '/';
            $categoryFiles = glob($categoryDir . '*.cache');
            foreach ($categoryFiles as $file) {
                $size = filesize($file);
                $totalSize += $size;
                $files[] = ['path' => $file, 'size' => $size, 'mtime' => filemtime($file)];
            }
        } else {
            $subDirs = ['api', 'images', 'static', 'data'];
            foreach ($subDirs as $dir) {
                $categoryDir = $this->cacheDir . $dir . '/';
                $categoryFiles = glob($categoryDir . '*.cache');
                foreach ($categoryFiles as $file) {
                    $size = filesize($file);
                    $totalSize += $size;
                    $files[] = ['path' => $file, 'size' => $size, 'mtime' => filemtime($file)];
                }
            }
        }
        
        // Si la taille dépasse la limite, supprimer les plus anciens
        if ($totalSize > $this->maxCacheSize) {
            usort($files, function($a, $b) {
                return $a['mtime'] - $b['mtime'];
            });
            
            $removed = 0;
            foreach ($files as $file) {
                if ($totalSize <= $this->maxCacheSize * 0.8) {
                    break;
                }
                
                unlink($file['path']);
                $totalSize -= $file['size'];
                $removed++;
            }
            
            error_log("Cache cleanup: $removed fichiers supprimés");
        }
    }
    
    /**
     * Obtient les statistiques du cache
     */
    public function getStats() {
        $stats = [
            'categories' => [],
            'total_size' => 0,
            'total_files' => 0,
            'expired_files' => 0
        ];
        
        $subDirs = ['api', 'images', 'static', 'data'];
        
        foreach ($subDirs as $category) {
            $categoryDir = $this->cacheDir . $category . '/';
            
            if (!is_dir($categoryDir)) {
                continue;
            }
            
            $files = glob($categoryDir . '*.cache');
            $categorySize = 0;
            $categoryFiles = 0;
            $categoryExpired = 0;
            
            foreach ($files as $file) {
                $size = filesize($file);
                $categorySize += $size;
                $categoryFiles++;
                
                // Vérifier l'expiration
                try {
                    $data = file_get_contents($file);
                    if ($data !== false) {
                        if ($this->compressionEnabled && function_exists('gzuncompress')) {
                            $uncompressed = @gzuncompress($data);
                            if ($uncompressed !== false) {
                                $data = $uncompressed;
                            }
                        }
                        
                        $cacheData = unserialize($data);
                        if ($cacheData !== false && time() > $cacheData['expires']) {
                            $categoryExpired++;
                        }
                    }
                } catch (Exception $e) {
                    $categoryExpired++;
                }
            }
            
            $stats['categories'][$category] = [
                'files' => $categoryFiles,
                'size' => $categorySize,
                'expired' => $categoryExpired,
                'size_human' => $this->formatBytes($categorySize)
            ];
            
            $stats['total_size'] += $categorySize;
            $stats['total_files'] += $categoryFiles;
            $stats['expired_files'] += $categoryExpired;
        }
        
        $stats['total_size_human'] = $this->formatBytes($stats['total_size']);
        $stats['max_size_human'] = $this->formatBytes($this->maxCacheSize);
        $stats['usage_percent'] = round(($stats['total_size'] / $this->maxCacheSize) * 100, 2);
        
        return $stats;
    }
    
    /**
     * Formate les octets en taille lisible
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Méthode helper pour le cache avec callback
     */
    public function remember($key, $callback, $ttl = null, $category = 'api') {
        $cached = $this->get($key, $category);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl, $category);
        
        return $value;
    }
    
    /**
     * Cache pour les réponses HTTP
     */
    public function cacheHttpResponse($key, $data, $headers = [], $ttl = null) {
        $responseData = [
            'data' => $data,
            'headers' => $headers,
            'timestamp' => time()
        ];
        
        return $this->set($key, $responseData, $ttl, 'api');
    }
    
    /**
     * Récupère une réponse HTTP cachée
     */
    public function getCachedHttpResponse($key) {
        $cached = $this->get($key, 'api');
        
        if ($cached === null) {
            return null;
        }
        
        // Ajouter les headers de cache
        if (isset($cached['headers'])) {
            foreach ($cached['headers'] as $header) {
                header($header);
            }
        }
        
        header('X-Cache: HIT');
        header('X-Cache-Timestamp: ' . date('r', $cached['timestamp']));
        
        return $cached['data'];
    }
}

?>