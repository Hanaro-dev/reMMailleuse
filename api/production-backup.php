<?php
/**
 * Production File System Backup for ReMmailleuse
 * 
 * Comprehensive backup system for production deployment
 * Designed to work with webcron services (no SSH required)
 */

// Sécurité - Vérification clé d'accès
$BACKUP_SECRET = 'remmailleuse_production_backup_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $BACKUP_SECRET) {
    http_response_code(403);
    die(json_encode(['error' => 'Accès refusé']));
}

// Configuration
$CONFIG = [
    'backup_dir' => dirname(__DIR__) . '/backups/production',
    'max_backups' => 10,
    'max_execution_time' => 240, // 4 minutes
    'compression_level' => 6,
    'include_patterns' => [
        '*.php', '*.html', '*.css', '*.js', '*.json', '*.txt', '*.md',
        '*.htaccess', '*.xml', '*.ico', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp'
    ],
    'exclude_directories' => [
        'node_modules', '.git', 'vendor', 'logs', 'backups', 'tmp', 'temp', '.cache'
    ],
    'critical_files' => [
        'index.html', 'admin/index.php', 'admin/login.html', 'api/auth.php',
        'api/contact.php', 'api/upload.php', 'assets/css/main.css',
        'assets/js/main.js', 'data/content.json'
    ]
];

// Inclure le système de logging
require_once 'Logger.php';
$logger = new Logger();

// Augmenter les limites
set_time_limit($CONFIG['max_execution_time']);
ini_set('memory_limit', '256M');

class ProductionBackupManager {
    private $config;
    private $logger;
    private $stats;
    private $rootPath;
    
    public function __construct($config, $logger) {
        $this->config = $config;
        $this->logger = $logger;
        $this->rootPath = dirname(__DIR__);
        $this->stats = [
            'start_time' => microtime(true),
            'files_processed' => 0,
            'files_backed_up' => 0,
            'total_size' => 0,
            'errors' => []
        ];
        
        $this->ensureBackupDirectory();
    }
    
    /**
     * Créer le backup complet
     */
    public function createBackup() {
        try {
            $this->logger->info('Début du backup production', [], 'backup');
            
            $backupName = 'production_' . date('Y-m-d_H-i-s');
            $backupPath = $this->config['backup_dir'] . '/' . $backupName;
            
            // Créer le dossier de backup
            if (!mkdir($backupPath, 0755, true)) {
                throw new Exception('Impossible de créer le dossier de backup');
            }
            
            // Scanner et copier les fichiers
            $this->scanAndBackupFiles($this->rootPath, $backupPath);
            
            // Créer le fichier d'informations
            $this->createBackupInfo($backupPath);
            
            // Comprimer le backup
            $zipPath = $this->compressBackup($backupPath, $backupName);
            
            // Nettoyer les anciens backups
            $this->cleanupOldBackups();
            
            $this->stats['end_time'] = microtime(true);
            $this->stats['duration'] = round($this->stats['end_time'] - $this->stats['start_time'], 2);
            $this->stats['compressed_size'] = file_exists($zipPath) ? filesize($zipPath) : 0;
            
            $this->logger->info('Backup production terminé', $this->stats, 'backup');
            
            return [
                'success' => true,
                'backup_name' => $backupName,
                'zip_path' => $zipPath,
                'stats' => $this->stats
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Erreur backup production: ' . $e->getMessage(), $this->stats, 'backup');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats
            ];
        }
    }
    
    /**
     * Scanner et sauvegarder les fichiers récursivement
     */
    private function scanAndBackupFiles($sourceDir, $backupDir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $this->stats['files_processed']++;
            
            $relativePath = str_replace($this->rootPath . '/', '', $file->getPathname());
            
            // Vérifier les exclusions
            if ($this->shouldExclude($relativePath)) {
                continue;
            }
            
            $targetPath = $backupDir . '/' . $relativePath;
            
            try {
                if ($file->isDir()) {
                    // Créer le dossier
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }
                } else {
                    // Copier le fichier
                    if ($this->shouldInclude($file->getFilename())) {
                        $targetDir = dirname($targetPath);
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        
                        if (copy($file->getPathname(), $targetPath)) {
                            $this->stats['files_backed_up']++;
                            $this->stats['total_size'] += $file->getSize();
                        }
                    }
                }
            } catch (Exception $e) {
                $this->stats['errors'][] = "Erreur avec $relativePath: " . $e->getMessage();
            }
        }
    }
    
    /**
     * Vérifier si un fichier doit être inclus
     */
    private function shouldInclude($filename) {
        foreach ($this->config['include_patterns'] as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifier si un chemin doit être exclu
     */
    private function shouldExclude($path) {
        foreach ($this->config['exclude_directories'] as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Créer le fichier d'informations du backup
     */
    private function createBackupInfo($backupPath) {
        $info = [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'php_version' => PHP_VERSION,
            'stats' => $this->stats,
            'critical_files_status' => $this->checkCriticalFiles()
        ];
        
        file_put_contents($backupPath . '/backup_info.json', json_encode($info, JSON_PRETTY_PRINT));
    }
    
    /**
     * Vérifier le statut des fichiers critiques
     */
    private function checkCriticalFiles() {
        $status = [];
        
        foreach ($this->config['critical_files'] as $file) {
            $filePath = $this->rootPath . '/' . $file;
            $status[$file] = [
                'exists' => file_exists($filePath),
                'size' => file_exists($filePath) ? filesize($filePath) : 0,
                'last_modified' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null
            ];
        }
        
        return $status;
    }
    
    /**
     * Comprimer le backup
     */
    private function compressBackup($backupPath, $backupName) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Extension ZIP non disponible');
        }
        
        $zipPath = $this->config['backup_dir'] . '/' . $backupName . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Impossible de créer l\'archive ZIP');
        }
        
        $this->addDirectoryToZip($zip, $backupPath, '');
        $zip->close();
        
        // Supprimer le dossier non compressé
        $this->removeDirectory($backupPath);
        
        return $zipPath;
    }
    
    /**
     * Ajouter un dossier à l'archive ZIP
     */
    private function addDirectoryToZip($zip, $dir, $base) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $relativePath = $base . substr($filePath, strlen($dir) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Supprimer un dossier récursivement
     */
    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
    
    /**
     * Nettoyer les anciens backups
     */
    private function cleanupOldBackups() {
        $backups = glob($this->config['backup_dir'] . '/production_*.zip');
        
        if (count($backups) > $this->config['max_backups']) {
            // Trier par date de modification
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $toDelete = array_slice($backups, 0, count($backups) - $this->config['max_backups']);
            
            foreach ($toDelete as $backup) {
                unlink($backup);
                $this->logger->info('Ancien backup supprimé: ' . basename($backup), [], 'backup');
            }
        }
    }
    
    /**
     * Créer le dossier de backup
     */
    private function ensureBackupDirectory() {
        if (!is_dir($this->config['backup_dir'])) {
            if (!mkdir($this->config['backup_dir'], 0755, true)) {
                throw new Exception('Impossible de créer le dossier de backup');
            }
        }
        
        // Créer .htaccess pour la sécurité
        $htaccessPath = $this->config['backup_dir'] . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccess = "# Sécurité dossier backups production\n";
            $htaccess .= "Require all denied\n";
            $htaccess .= "Options -Indexes\n";
            file_put_contents($htaccessPath, $htaccess);
        }
    }
    
    /**
     * Lister les backups disponibles
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->config['backup_dir'] . '/production_*.zip');
        
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return $backups;
    }
    
    /**
     * Formater les octets
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Traitement des requêtes
$action = $_GET['action'] ?? 'backup';
$manager = new ProductionBackupManager($CONFIG, $logger);

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'backup':
            $result = $manager->createBackup();
            break;
            
        case 'list':
            $result = [
                'success' => true,
                'backups' => $manager->listBackups()
            ];
            break;
            
        case 'status':
            $result = [
                'success' => true,
                'backup_dir' => $CONFIG['backup_dir'],
                'space_free' => disk_free_space($CONFIG['backup_dir']),
                'space_total' => disk_total_space($CONFIG['backup_dir']),
                'backups_count' => count($manager->listBackups())
            ];
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Action non reconnue'];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>