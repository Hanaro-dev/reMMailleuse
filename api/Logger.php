<?php
/**
 * ===== SYSTÈME DE LOGS CENTRALISÉ - SITE REMMAILLEUSE =====
 * Gestionnaire de logs unifié avec rotation, filtrage et analyse
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class Logger {
    private $config;
    private $logDir;
    private $channels;
    private $formatters;
    private $filters;
    private $handlers;
    private $context;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'log_dir' => dirname(__DIR__) . '/logs/',
            'default_channel' => 'app',
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'max_files' => 30,
            'date_format' => 'Y-m-d H:i:s',
            'timezone' => 'Europe/Paris',
            'enable_console' => false,
            'enable_database' => false,
            'enable_email_alerts' => true,
            'email_alert_levels' => ['CRITICAL', 'EMERGENCY'],
            'enable_compression' => true,
            'enable_encryption' => false,
            'encryption_key' => null
        ], $config);
        
        $this->logDir = $this->config['log_dir'];
        $this->channels = [];
        $this->formatters = [];
        $this->filters = [];
        $this->handlers = [];
        $this->context = [];
        
        $this->initializeLogger();
    }
    
    /**
     * Initialiser le logger
     */
    private function initializeLogger() {
        // Créer le répertoire de logs
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Configurer le timezone
        date_default_timezone_set($this->config['timezone']);
        
        // Enregistrer les canaux par défaut
        $this->addChannel('app', 'Application générale');
        $this->addChannel('security', 'Sécurité et authentification');
        $this->addChannel('api', 'APIs et endpoints');
        $this->addChannel('database', 'Base de données');
        $this->addChannel('cache', 'Système de cache');
        $this->addChannel('email', 'Envoi d\'emails');
        $this->addChannel('upload', 'Upload de fichiers');
        $this->addChannel('performance', 'Performance et monitoring');
        $this->addChannel('error', 'Erreurs système');
        $this->addChannel('debug', 'Debug et développement');
        
        // Enregistrer les formatteurs par défaut
        $this->addFormatter('default', [$this, 'defaultFormatter']);
        $this->addFormatter('json', [$this, 'jsonFormatter']);
        $this->addFormatter('detailed', [$this, 'detailedFormatter']);
        
        // Enregistrer les handlers par défaut
        $this->addHandler('file', [$this, 'fileHandler']);
        if ($this->config['enable_console']) {
            $this->addHandler('console', [$this, 'consoleHandler']);
        }
        if ($this->config['enable_database']) {
            $this->addHandler('database', [$this, 'databaseHandler']);
        }
        
        // Contexte global
        $this->context = [
            'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_id' => uniqid('req_', true),
            'session_id' => session_id() ?: 'no_session'
        ];
    }
    
    /**
     * Ajouter un canal de log
     */
    public function addChannel($name, $description = '') {
        $this->channels[$name] = [
            'name' => $name,
            'description' => $description,
            'created' => time(),
            'file' => $this->logDir . $name . '.log'
        ];
    }
    
    /**
     * Ajouter un formatteur
     */
    public function addFormatter($name, $formatter) {
        if (is_callable($formatter)) {
            $this->formatters[$name] = $formatter;
        }
    }
    
    /**
     * Ajouter un filtre
     */
    public function addFilter($name, $filter) {
        if (is_callable($filter)) {
            $this->filters[$name] = $filter;
        }
    }
    
    /**
     * Ajouter un handler
     */
    public function addHandler($name, $handler) {
        if (is_callable($handler)) {
            $this->handlers[$name] = $handler;
        }
    }
    
    /**
     * Logger un message
     */
    public function log($level, $message, $context = [], $channel = null) {
        $channel = $channel ?: $this->config['default_channel'];
        
        // Vérifier si le canal existe
        if (!isset($this->channels[$channel])) {
            $this->addChannel($channel);
        }
        
        // Créer l'entrée de log
        $logEntry = [
            'timestamp' => time(),
            'datetime' => date($this->config['date_format']),
            'level' => strtoupper($level),
            'channel' => $channel,
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
        
        // Appliquer les filtres
        foreach ($this->filters as $filter) {
            if (!$filter($logEntry)) {
                return; // Le filtre a rejeté l'entrée
            }
        }
        
        // Traiter avec les handlers
        foreach ($this->handlers as $handler) {
            try {
                $handler($logEntry);
            } catch (Exception $e) {
                // Ne pas faire planter le système si un handler échoue
                error_log("Logger handler error: " . $e->getMessage());
            }
        }
        
        // Alertes email pour les niveaux critiques
        if (in_array($logEntry['level'], $this->config['email_alert_levels'])) {
            $this->sendEmailAlert($logEntry);
        }
        
        // Nettoyage périodique
        if (mt_rand(1, 1000) <= 1) { // 0.1% de chance
            $this->cleanupOldLogs();
        }
    }
    
    /**
     * Méthodes de logging par niveau
     */
    public function emergency($message, $context = [], $channel = null) {
        $this->log('EMERGENCY', $message, $context, $channel);
    }
    
    public function alert($message, $context = [], $channel = null) {
        $this->log('ALERT', $message, $context, $channel);
    }
    
    public function critical($message, $context = [], $channel = null) {
        $this->log('CRITICAL', $message, $context, $channel);
    }
    
    public function error($message, $context = [], $channel = null) {
        $this->log('ERROR', $message, $context, $channel);
    }
    
    public function warning($message, $context = [], $channel = null) {
        $this->log('WARNING', $message, $context, $channel);
    }
    
    public function notice($message, $context = [], $channel = null) {
        $this->log('NOTICE', $message, $context, $channel);
    }
    
    public function info($message, $context = [], $channel = null) {
        $this->log('INFO', $message, $context, $channel);
    }
    
    public function debug($message, $context = [], $channel = null) {
        $this->log('DEBUG', $message, $context, $channel);
    }
    
    /**
     * Logger avec canal spécifique
     */
    public function security($level, $message, $context = []) {
        $this->log($level, $message, $context, 'security');
    }
    
    public function api($level, $message, $context = []) {
        $this->log($level, $message, $context, 'api');
    }
    
    public function database($level, $message, $context = []) {
        $this->log($level, $message, $context, 'database');
    }
    
    public function performance($level, $message, $context = []) {
        $this->log($level, $message, $context, 'performance');
    }
    
    /**
     * Formatteur par défaut
     */
    private function defaultFormatter($logEntry) {
        return sprintf(
            "[%s] [%s] [%s] %s %s\n",
            $logEntry['datetime'],
            $logEntry['level'],
            $logEntry['channel'],
            $logEntry['message'],
            !empty($logEntry['context']) ? json_encode($logEntry['context']) : ''
        );
    }
    
    /**
     * Formatteur JSON
     */
    private function jsonFormatter($logEntry) {
        return json_encode($logEntry) . "\n";
    }
    
    /**
     * Formatteur détaillé
     */
    private function detailedFormatter($logEntry) {
        $context = $logEntry['context'];
        $formatted = sprintf(
            "[%s] [%s] [%s]\n",
            $logEntry['datetime'],
            $logEntry['level'],
            $logEntry['channel']
        );
        $formatted .= "Message: " . $logEntry['message'] . "\n";
        $formatted .= "Memory: " . $this->formatBytes($logEntry['memory_usage']) . "\n";
        $formatted .= "Peak Memory: " . $this->formatBytes($logEntry['memory_peak']) . "\n";
        
        if (!empty($context)) {
            $formatted .= "Context:\n";
            foreach ($context as $key => $value) {
                $formatted .= "  $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }
        $formatted .= str_repeat('-', 80) . "\n";
        
        return $formatted;
    }
    
    /**
     * Handler fichier
     */
    private function fileHandler($logEntry) {
        $channel = $this->channels[$logEntry['channel']];
        $formatter = $this->formatters['default'];
        
        $formatted = $formatter($logEntry);
        
        // Chiffrement si activé
        if ($this->config['enable_encryption'] && $this->config['encryption_key']) {
            $formatted = $this->encrypt($formatted);
        }
        
        file_put_contents($channel['file'], $formatted, FILE_APPEND | LOCK_EX);
        
        // Rotation si nécessaire
        $this->rotateLogIfNeeded($channel['file']);
    }
    
    /**
     * Handler console
     */
    private function consoleHandler($logEntry) {
        if (php_sapi_name() === 'cli') {
            $formatter = $this->formatters['default'];
            echo $formatter($logEntry);
        }
    }
    
    /**
     * Handler base de données
     */
    private function databaseHandler($logEntry) {
        // À implémenter si nécessaire
        // Nécessite une connexion à la base de données
    }
    
    /**
     * Rotation des logs
     */
    private function rotateLogIfNeeded($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        $fileSize = filesize($logFile);
        if ($fileSize < $this->config['max_file_size']) {
            return;
        }
        
        // Créer le nom du fichier archivé
        $timestamp = date('Y-m-d_H-i-s');
        $archiveFile = $logFile . '.' . $timestamp;
        
        // Compresser si activé
        if ($this->config['enable_compression'] && function_exists('gzopen')) {
            $archiveFile .= '.gz';
            $this->compressFile($logFile, $archiveFile);
            unlink($logFile);
        } else {
            rename($logFile, $archiveFile);
        }
        
        // Nettoyer les anciens fichiers
        $this->cleanupOldArchives($logFile);
    }
    
    /**
     * Compresser un fichier
     */
    private function compressFile($source, $destination) {
        $sourceHandle = fopen($source, 'rb');
        $destHandle = gzopen($destination, 'wb9');
        
        while (!feof($sourceHandle)) {
            gzwrite($destHandle, fread($sourceHandle, 8192));
        }
        
        fclose($sourceHandle);
        gzclose($destHandle);
    }
    
    /**
     * Nettoyer les anciennes archives
     */
    private function cleanupOldArchives($baseFile) {
        $pattern = $baseFile . '.*';
        $files = glob($pattern);
        
        if (count($files) <= $this->config['max_files']) {
            return;
        }
        
        // Trier par date de modification
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Supprimer les plus anciens
        $toDelete = array_slice($files, 0, count($files) - $this->config['max_files']);
        foreach ($toDelete as $file) {
            unlink($file);
        }
    }
    
    /**
     * Nettoyer tous les anciens logs
     */
    public function cleanupOldLogs() {
        foreach ($this->channels as $channel) {
            $this->cleanupOldArchives($channel['file']);
        }
    }
    
    /**
     * Envoyer une alerte email
     */
    private function sendEmailAlert($logEntry) {
        if (!$this->config['enable_email_alerts']) {
            return;
        }
        
        $subject = "ALERTE LOG [{$logEntry['level']}] - {$logEntry['channel']}";
        $message = "Une entrée de log critique a été détectée:\n\n";
        $message .= "Niveau: {$logEntry['level']}\n";
        $message .= "Canal: {$logEntry['channel']}\n";
        $message .= "Date: {$logEntry['datetime']}\n";
        $message .= "Message: {$logEntry['message']}\n\n";
        $message .= "Contexte:\n" . json_encode($logEntry['context'], JSON_PRETTY_PRINT);
        
        // Utiliser l'EmailManager si disponible
        if (class_exists('EmailManager')) {
            try {
                $emailManager = new EmailManager();
                $emailManager->sendAdminAlert($subject, $message);
            } catch (Exception $e) {
                error_log("Erreur envoi alerte email: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Chiffrer du contenu
     */
    private function encrypt($data) {
        if (!$this->config['encryption_key']) {
            return $data;
        }
        
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, $this->config['encryption_key'], 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Déchiffrer du contenu
     */
    private function decrypt($data) {
        if (!$this->config['encryption_key']) {
            return $data;
        }
        
        $data = base64_decode($data);
        $method = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $method, $this->config['encryption_key'], 0, $iv);
    }
    
    /**
     * Obtenir les statistiques des logs
     */
    public function getStats() {
        $stats = [
            'channels' => [],
            'total_size' => 0,
            'total_files' => 0
        ];
        
        foreach ($this->channels as $name => $channel) {
            $files = glob($channel['file'] . '*');
            $channelSize = 0;
            $channelFiles = count($files);
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $channelSize += filesize($file);
                }
            }
            
            $stats['channels'][$name] = [
                'files' => $channelFiles,
                'size' => $channelSize,
                'size_formatted' => $this->formatBytes($channelSize),
                'latest_file' => $channel['file'],
                'latest_modified' => file_exists($channel['file']) ? filemtime($channel['file']) : 0
            ];
            
            $stats['total_size'] += $channelSize;
            $stats['total_files'] += $channelFiles;
        }
        
        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Lire les logs d'un canal
     */
    public function readLogs($channel, $lines = 100, $level = null) {
        if (!isset($this->channels[$channel])) {
            throw new InvalidArgumentException("Canal '$channel' non trouvé");
        }
        
        $logFile = $this->channels[$channel]['file'];
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $file = new SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $parsed = $this->parseLogLine($line);
                if ($parsed && (!$level || $parsed['level'] === strtoupper($level))) {
                    $logs[] = $parsed;
                }
            }
            $file->next();
        }
        
        return $logs;
    }
    
    /**
     * Parser une ligne de log
     */
    private function parseLogLine($line) {
        // Format: [datetime] [level] [channel] message context
        if (preg_match('/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] (.+)$/', $line, $matches)) {
            $contextStart = strrpos($matches[4], '{');
            $message = $contextStart !== false ? substr($matches[4], 0, $contextStart) : $matches[4];
            $context = $contextStart !== false ? substr($matches[4], $contextStart) : '';
            
            return [
                'datetime' => $matches[1],
                'level' => $matches[2],
                'channel' => $matches[3],
                'message' => trim($message),
                'context' => $context ? json_decode($context, true) : []
            ];
        }
        
        return null;
    }
    
    /**
     * Rechercher dans les logs
     */
    public function search($query, $channels = null, $levels = null, $limit = 100) {
        $channels = $channels ?: array_keys($this->channels);
        $results = [];
        
        foreach ($channels as $channel) {
            if (!isset($this->channels[$channel])) {
                continue;
            }
            
            $logs = $this->readLogs($channel, 1000);
            foreach ($logs as $log) {
                if ($levels && !in_array($log['level'], $levels)) {
                    continue;
                }
                
                if (stripos($log['message'], $query) !== false ||
                    stripos(json_encode($log['context']), $query) !== false) {
                    $results[] = array_merge($log, ['channel' => $channel]);
                    
                    if (count($results) >= $limit) {
                        break 2;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Formater les octets
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Ajouter du contexte global
     */
    public function addContext($key, $value) {
        $this->context[$key] = $value;
    }
    
    /**
     * Retirer du contexte global
     */
    public function removeContext($key) {
        unset($this->context[$key]);
    }
    
    /**
     * Obtenir le contexte global
     */
    public function getContext() {
        return $this->context;
    }
}

/**
 * Instance globale pour faciliter l'usage
 */
function getLogger($config = []) {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new Logger($config);
    }
    
    return $instance;
}

/**
 * Fonctions helper globales
 */
function logInfo($message, $context = [], $channel = null) {
    getLogger()->info($message, $context, $channel);
}

function logError($message, $context = [], $channel = null) {
    getLogger()->error($message, $context, $channel);
}

function logWarning($message, $context = [], $channel = null) {
    getLogger()->warning($message, $context, $channel);
}

function logDebug($message, $context = [], $channel = null) {
    getLogger()->debug($message, $context, $channel);
}

function logSecurity($message, $context = [], $level = 'WARNING') {
    getLogger()->security($level, $message, $context);
}

function logAPI($message, $context = [], $level = 'INFO') {
    getLogger()->api($level, $message, $context);
}

function logDatabase($message, $context = [], $level = 'INFO') {
    getLogger()->database($level, $message, $context);
}

function logPerformance($message, $context = [], $level = 'INFO') {
    getLogger()->performance($level, $message, $context);
}

?>