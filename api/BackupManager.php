<?php
/**
 * Gestionnaire de sauvegarde automatique des données JSON
 * Créé des sauvegardes horodatées avec rotation et compression
 */

class BackupManager {
    private $dataDir;
    private $backupDir;
    private $config;
    
    public function __construct($dataDir = null, $backupDir = null) {
        $this->dataDir = $dataDir ?: __DIR__ . '/../data/';
        $this->backupDir = $backupDir ?: __DIR__ . '/../backups/';
        
        $this->config = [
            'max_backups' => 50,           // Nombre max de sauvegardes à conserver
            'backup_interval' => 3600,     // Intervalle en secondes (1h)
            'compression' => true,         // Activer la compression
            'rotate_days' => 30,           // Rotation après X jours
            'files_to_backup' => [
                'content.json',
                'gallery.json', 
                'services.json',
                'settings.json'
            ]
        ];
        
        $this->ensureBackupDir();
    }
    
    /**
     * Crée une sauvegarde complète
     */
    public function createBackup($type = 'auto') {
        $timestamp = date('Y-m-d_H-i-s');
        $backupId = $timestamp . '_' . $type;
        
        try {
            $backup = [
                'id' => $backupId,
                'timestamp' => time(),
                'type' => $type,
                'size' => 0,
                'files' => [],
                'metadata' => [
                    'created_at' => date('c'),
                    'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'system',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'system'
                ]
            ];
            
            // Sauvegarder chaque fichier JSON
            foreach ($this->config['files_to_backup'] as $filename) {
                $filePath = $this->dataDir . $filename;
                
                if (file_exists($filePath)) {
                    $fileData = $this->backupFile($filePath, $backupId);
                    $backup['files'][] = $fileData;
                    $backup['size'] += $fileData['size'];
                }
            }
            
            // Créer le fichier de métadonnées
            $metadataPath = $this->backupDir . $backupId . '.json';
            file_put_contents($metadataPath, json_encode($backup, JSON_PRETTY_PRINT));
            
            // Créer une archive complète si compression activée
            if ($this->config['compression']) {
                $this->createCompressedBackup($backupId, $backup);
            }
            
            // Nettoyer les anciennes sauvegardes
            $this->cleanupOldBackups();
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'files_count' => count($backup['files']),
                'total_size' => $backup['size'],
                'compressed' => $this->config['compression']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sauvegarde un fichier individuel
     */
    private function backupFile($filePath, $backupId) {
        $filename = basename($filePath);
        $backupPath = $this->backupDir . $backupId . '_' . $filename;
        
        // Copier le fichier
        if (!copy($filePath, $backupPath)) {
            throw new Exception("Impossible de sauvegarder {$filename}");
        }
        
        // Calculer les informations du fichier
        $filesize = filesize($filePath);
        $filehash = hash_file('sha256', $filePath);
        
        return [
            'filename' => $filename,
            'backup_path' => $backupPath,
            'size' => $filesize,
            'hash' => $filehash,
            'last_modified' => filemtime($filePath)
        ];
    }
    
    /**
     * Crée une archive compressée de la sauvegarde
     */
    private function createCompressedBackup($backupId, $backup) {
        if (!class_exists('ZipArchive')) {
            return; // ZIP non disponible
        }
        
        $zipPath = $this->backupDir . $backupId . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Ajouter les fichiers de données
            foreach ($backup['files'] as $file) {
                $zip->addFile($file['backup_path'], $file['filename']);
            }
            
            // Ajouter les métadonnées
            $zip->addFromString('backup_info.json', json_encode($backup, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            // Supprimer les fichiers individuels pour économiser l'espace
            foreach ($backup['files'] as $file) {
                unlink($file['backup_path']);
            }
        }
    }
    
    /**
     * Nettoie les anciennes sauvegardes
     */
    private function cleanupOldBackups() {
        $backups = $this->listBackups();
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Supprimer les sauvegardes en excès
        if (count($backups) > $this->config['max_backups']) {
            $toDelete = array_slice($backups, $this->config['max_backups']);
            
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['id']);
            }
        }
        
        // Supprimer les sauvegardes trop anciennes
        $cutoffTime = time() - ($this->config['rotate_days'] * 24 * 60 * 60);
        
        foreach ($backups as $backup) {
            if ($backup['timestamp'] < $cutoffTime) {
                $this->deleteBackup($backup['id']);
            }
        }
    }
    
    /**
     * Supprime une sauvegarde
     */
    private function deleteBackup($backupId) {
        $files = glob($this->backupDir . $backupId . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Liste toutes les sauvegardes disponibles
     */
    public function listBackups() {
        $backups = [];
        $metadataFiles = glob($this->backupDir . '*.json');
        
        foreach ($metadataFiles as $metadataFile) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            
            if ($metadata && isset($metadata['id'])) {
                $backups[] = $metadata;
            }
        }
        
        return $backups;
    }
    
    /**
     * Restaure une sauvegarde
     */
    public function restoreBackup($backupId) {
        try {
            $backupPath = $this->backupDir . $backupId . '.zip';
            $metadataPath = $this->backupDir . $backupId . '.json';
            
            if (!file_exists($backupPath) && !file_exists($metadataPath)) {
                throw new Exception("Sauvegarde introuvable: {$backupId}");
            }
            
            $restored = 0;
            
            // Restaurer depuis ZIP si disponible
            if (file_exists($backupPath) && class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                
                if ($zip->open($backupPath) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        
                        if ($filename !== 'backup_info.json' && pathinfo($filename, PATHINFO_EXTENSION) === 'json') {
                            $content = $zip->getFromIndex($i);
                            $targetPath = $this->dataDir . $filename;
                            
                            if (file_put_contents($targetPath, $content) !== false) {
                                $restored++;
                            }
                        }
                    }
                    
                    $zip->close();
                }
            } else {
                // Restaurer depuis les fichiers individuels
                $metadata = json_decode(file_get_contents($metadataPath), true);
                
                foreach ($metadata['files'] as $file) {
                    $sourcePath = $file['backup_path'];
                    $targetPath = $this->dataDir . $file['filename'];
                    
                    if (file_exists($sourcePath) && copy($sourcePath, $targetPath)) {
                        $restored++;
                    }
                }
            }
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'files_restored' => $restored
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifie si une sauvegarde est nécessaire
     */
    public function needsBackup() {
        $backups = $this->listBackups();
        
        if (empty($backups)) {
            return true; // Aucune sauvegarde existante
        }
        
        // Trouver la sauvegarde la plus récente
        $latestBackup = max($backups, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });
        
        $timeSinceLastBackup = time() - $latestBackup['timestamp'];
        
        return $timeSinceLastBackup > $this->config['backup_interval'];
    }
    
    /**
     * Sauvegarde automatique si nécessaire
     */
    public function autoBackup() {
        if ($this->needsBackup()) {
            return $this->createBackup('auto');
        }
        
        return [
            'success' => true,
            'skipped' => true,
            'reason' => 'Backup not needed yet'
        ];
    }
    
    /**
     * Obtient les statistiques des sauvegardes
     */
    public function getBackupStats() {
        $backups = $this->listBackups();
        $totalSize = 0;
        $types = [];
        
        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
            $types[$backup['type']] = ($types[$backup['type']] ?? 0) + 1;
        }
        
        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'types' => $types,
            'oldest_backup' => !empty($backups) ? min($backups, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            })['id'] : null,
            'newest_backup' => !empty($backups) ? max($backups, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            })['id'] : null
        ];
    }
    
    /**
     * Assure l'existence du dossier de sauvegarde
     */
    private function ensureBackupDir() {
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier de sauvegarde");
            }
        }
        
        // Créer un fichier .htaccess pour la sécurité
        $htaccessPath = $this->backupDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Sécurité dossier backups\n";
            $htaccessContent .= "Require all denied\n";
            $htaccessContent .= "Options -Indexes\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
    
    /**
     * Formate les octets en unités lisibles
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

?>