<?php
/**
 * ===== GESTIONNAIRE DE NETTOYAGE - SITE REMMAILLEUSE =====
 * Système centralisé et optimisé pour le nettoyage des fichiers temporaires
 * Compatible Infomaniak/PHP 8+
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class CleanupManager {
    private $settings;
    private $logFile;
    private $cleanupLockFile;
    
    // Configuration par défaut
    private $defaultConfig = [
        'rate_limit_files' => [
            'pattern' => 'rate_limit_*.txt',
            'max_age' => 3600, // 1 heure
            'directory' => '../temp/'
        ],
        'auth_attempts' => [
            'file' => '../temp/login_attempts.json',
            'max_age' => 86400 // 24 heures
        ],
        'upload_temp' => [
            'directory' => '../uploads/temp/',
            'max_age' => 1800 // 30 minutes
        ],
        'logs' => [
            'directory' => '../logs/',
            'max_age' => 2592000, // 30 jours
            'max_size' => 10485760 // 10MB par fichier
        ],
        'session_files' => [
            'directory' => sys_get_temp_dir(),
            'pattern' => 'sess_*',
            'max_age' => 86400 // 24 heures
        ]
    ];
    
    public function __construct($settingsPath = '../data/settings.json') {
        $this->loadSettings($settingsPath);
        $this->logFile = '../logs/cleanup.log';
        $this->cleanupLockFile = '../temp/cleanup.lock';
        $this->createDirectories();
    }
    
    /**
     * Charge les paramètres depuis settings.json
     */
    private function loadSettings($settingsPath) {
        if (file_exists($settingsPath)) {
            $content = file_get_contents($settingsPath);
            $this->settings = json_decode($content, true);
        } else {
            $this->settings = [];
        }
    }
    
    /**
     * Crée les répertoires nécessaires
     */
    private function createDirectories() {
        $dirs = [
            dirname($this->logFile),
            dirname($this->cleanupLockFile),
            $this->defaultConfig['rate_limit_files']['directory'],
            $this->defaultConfig['upload_temp']['directory']
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Enregistre un événement dans les logs
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        
        // Rotation des logs si nécessaire
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->defaultConfig['logs']['max_size']) {
            $this->rotateLog();
        }
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Effectue la rotation des logs
     */
    private function rotateLog() {
        if (file_exists($this->logFile)) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            rename($this->logFile, $backupFile);
            $this->log("Log roté vers $backupFile");
        }
    }
    
    /**
     * Vérifie si un nettoyage est déjà en cours
     */
    private function isCleanupRunning() {
        if (!file_exists($this->cleanupLockFile)) {
            return false;
        }
        
        $lockTime = filemtime($this->cleanupLockFile);
        $maxLockTime = 300; // 5 minutes max
        
        if (time() - $lockTime > $maxLockTime) {
            // Lock trop ancien, le supprimer
            unlink($this->cleanupLockFile);
            return false;
        }
        
        return true;
    }
    
    /**
     * Crée un verrou de nettoyage
     */
    private function createCleanupLock() {
        file_put_contents($this->cleanupLockFile, time());
    }
    
    /**
     * Supprime le verrou de nettoyage
     */
    private function removeCleanupLock() {
        if (file_exists($this->cleanupLockFile)) {
            unlink($this->cleanupLockFile);
        }
    }
    
    /**
     * Nettoie les fichiers de rate limiting
     */
    public function cleanupRateLimitFiles() {
        $config = $this->defaultConfig['rate_limit_files'];
        $cleaned = 0;
        
        try {
            $files = glob($config['directory'] . $config['pattern']);
            $now = time();
            
            foreach ($files as $file) {
                $fileAge = $now - filemtime($file);
                
                if ($fileAge > $config['max_age']) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
            
            if ($cleaned > 0) {
                $this->log("Nettoyé $cleaned fichiers de rate limiting");
            }
            
        } catch (Exception $e) {
            $this->log("Erreur nettoyage rate limiting: " . $e->getMessage(), 'ERROR');
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoie les tentatives d'authentification
     */
    public function cleanupAuthAttempts() {
        $config = $this->defaultConfig['auth_attempts'];
        $cleaned = 0;
        
        try {
            if (!file_exists($config['file'])) {
                return 0;
            }
            
            $attempts = json_decode(file_get_contents($config['file']), true) ?: [];
            $now = time();
            $cleanedAttempts = [];
            
            foreach ($attempts as $ip => $data) {
                $lastAttempt = $data['last_attempt'] ?? 0;
                
                if ($now - $lastAttempt <= $config['max_age']) {
                    $cleanedAttempts[$ip] = $data;
                } else {
                    $cleaned++;
                }
            }
            
            if ($cleaned > 0) {
                file_put_contents($config['file'], json_encode($cleanedAttempts));
                $this->log("Nettoyé $cleaned tentatives d'authentification expirées");
            }
            
        } catch (Exception $e) {
            $this->log("Erreur nettoyage auth attempts: " . $e->getMessage(), 'ERROR');
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoie les fichiers temporaires d'upload
     */
    public function cleanupUploadTemp() {
        $config = $this->defaultConfig['upload_temp'];
        $cleaned = 0;
        
        try {
            if (!is_dir($config['directory'])) {
                return 0;
            }
            
            $files = glob($config['directory'] . '*');
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileAge = $now - filemtime($file);
                    
                    if ($fileAge > $config['max_age']) {
                        if (unlink($file)) {
                            $cleaned++;
                        }
                    }
                }
            }
            
            if ($cleaned > 0) {
                $this->log("Nettoyé $cleaned fichiers temporaires d'upload");
            }
            
        } catch (Exception $e) {
            $this->log("Erreur nettoyage upload temp: " . $e->getMessage(), 'ERROR');
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoie les anciens logs
     */
    public function cleanupOldLogs() {
        $config = $this->defaultConfig['logs'];
        $cleaned = 0;
        
        try {
            if (!is_dir($config['directory'])) {
                return 0;
            }
            
            $files = glob($config['directory'] . '*.log*');
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file) && $file !== $this->logFile) {
                    $fileAge = $now - filemtime($file);
                    
                    if ($fileAge > $config['max_age']) {
                        if (unlink($file)) {
                            $cleaned++;
                        }
                    }
                }
            }
            
            if ($cleaned > 0) {
                $this->log("Nettoyé $cleaned anciens fichiers de logs");
            }
            
        } catch (Exception $e) {
            $this->log("Erreur nettoyage logs: " . $e->getMessage(), 'ERROR');
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoie les fichiers de session PHP
     */
    public function cleanupSessionFiles() {
        $config = $this->defaultConfig['session_files'];
        $cleaned = 0;
        
        try {
            $files = glob($config['directory'] . '/' . $config['pattern']);
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileAge = $now - filemtime($file);
                    
                    if ($fileAge > $config['max_age']) {
                        if (unlink($file)) {
                            $cleaned++;
                        }
                    }
                }
            }
            
            if ($cleaned > 0) {
                $this->log("Nettoyé $cleaned fichiers de session");
            }
            
        } catch (Exception $e) {
            $this->log("Erreur nettoyage sessions: " . $e->getMessage(), 'ERROR');
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoyage complet (toutes les catégories)
     */
    public function runFullCleanup() {
        if ($this->isCleanupRunning()) {
            $this->log("Nettoyage déjà en cours, abandon", 'WARNING');
            return false;
        }
        
        $this->createCleanupLock();
        $startTime = microtime(true);
        
        try {
            $this->log("Début du nettoyage complet");
            
            $results = [
                'rate_limit' => $this->cleanupRateLimitFiles(),
                'auth_attempts' => $this->cleanupAuthAttempts(),
                'upload_temp' => $this->cleanupUploadTemp(),
                'old_logs' => $this->cleanupOldLogs(),
                'session_files' => $this->cleanupSessionFiles()
            ];
            
            $totalCleaned = array_sum($results);
            $duration = round(microtime(true) - $startTime, 3);
            
            $this->log("Nettoyage terminé: $totalCleaned fichiers supprimés en {$duration}s");
            
            return [
                'success' => true,
                'cleaned' => $totalCleaned,
                'details' => $results,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            $this->log("Erreur lors du nettoyage complet: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } finally {
            $this->removeCleanupLock();
        }
    }
    
    /**
     * Nettoyage rapide (seulement les fichiers critiques)
     */
    public function runQuickCleanup() {
        $startTime = microtime(true);
        
        try {
            $results = [
                'rate_limit' => $this->cleanupRateLimitFiles(),
                'auth_attempts' => $this->cleanupAuthAttempts()
            ];
            
            $totalCleaned = array_sum($results);
            $duration = round(microtime(true) - $startTime, 3);
            
            if ($totalCleaned > 0) {
                $this->log("Nettoyage rapide: $totalCleaned fichiers supprimés en {$duration}s");
            }
            
            return [
                'success' => true,
                'cleaned' => $totalCleaned,
                'details' => $results,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            $this->log("Erreur lors du nettoyage rapide: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Nettoyage automatique intelligent
     * Décide du type de nettoyage à effectuer selon la charge
     */
    public function runSmartCleanup() {
        // Vérifier la charge système
        $loadAvg = sys_getloadavg();
        $cpuLoad = $loadAvg[0] ?? 0;
        
        // Vérifier l'espace disque
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        // Décider du type de nettoyage
        if ($cpuLoad > 2.0 || $usagePercent > 90) {
            // Système surchargé, nettoyage rapide seulement
            return $this->runQuickCleanup();
        } else {
            // Système OK, nettoyage complet
            return $this->runFullCleanup();
        }
    }
    
    /**
     * Obtient les statistiques de nettoyage
     */
    public function getCleanupStats() {
        $stats = [
            'rate_limit_files' => 0,
            'auth_attempts' => 0,
            'upload_temp' => 0,
            'old_logs' => 0,
            'session_files' => 0
        ];
        
        try {
            // Compter les fichiers de rate limiting
            $files = glob($this->defaultConfig['rate_limit_files']['directory'] . $this->defaultConfig['rate_limit_files']['pattern']);
            $stats['rate_limit_files'] = count($files);
            
            // Compter les tentatives d'auth
            $authFile = $this->defaultConfig['auth_attempts']['file'];
            if (file_exists($authFile)) {
                $attempts = json_decode(file_get_contents($authFile), true) ?: [];
                $stats['auth_attempts'] = count($attempts);
            }
            
            // Compter les fichiers temp d'upload
            $uploadDir = $this->defaultConfig['upload_temp']['directory'];
            if (is_dir($uploadDir)) {
                $files = glob($uploadDir . '*');
                $stats['upload_temp'] = count(array_filter($files, 'is_file'));
            }
            
            // Compter les anciens logs
            $logDir = $this->defaultConfig['logs']['directory'];
            if (is_dir($logDir)) {
                $files = glob($logDir . '*.log*');
                $stats['old_logs'] = count(array_filter($files, function($file) {
                    return is_file($file) && $file !== $this->logFile;
                }));
            }
            
            // Compter les fichiers de session
            $sessionDir = $this->defaultConfig['session_files']['directory'];
            $files = glob($sessionDir . '/' . $this->defaultConfig['session_files']['pattern']);
            $stats['session_files'] = count($files);
            
        } catch (Exception $e) {
            $this->log("Erreur calcul statistiques: " . $e->getMessage(), 'ERROR');
        }
        
        return $stats;
    }
    
    /**
     * Teste le système de nettoyage
     */
    public function testCleanup() {
        $testResults = [];
        
        // Créer des fichiers de test
        $testFile = $this->defaultConfig['rate_limit_files']['directory'] . 'test_cleanup.txt';
        file_put_contents($testFile, 'test');
        
        // Tester le nettoyage
        $result = $this->runQuickCleanup();
        $testResults['quick_cleanup'] = $result['success'];
        
        // Nettoyer le fichier de test
        if (file_exists($testFile)) {
            unlink($testFile);
        }
        
        return $testResults;
    }
}