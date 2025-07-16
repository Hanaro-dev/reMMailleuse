<?php
/**
 * Helpers pour le rate limiting
 */

require_once 'RateLimiter.php';

/**
 * Obtient une instance du RateLimiter
 */
function getRateLimiter() {
    static $rateLimiter = null;
    
    if ($rateLimiter === null) {
        $config = [
            'storage_type' => 'file',
            'storage_path' => dirname(__DIR__) . '/temp/',
            'global_limit' => 100,
            'global_window' => 3600,
            'endpoints' => [
                'contact' => [
                    'limit' => 5,
                    'window' => 300
                ],
                'auth' => [
                    'limit' => 10,
                    'window' => 900
                ]
            ]
        ];
        
        $rateLimiter = new RateLimiter($config);
    }
    
    return $rateLimiter;
}

/**
 * Fonctions de logging pour compatibility
 */
function logAPI($message, $context = [], $level = 'INFO') {
    $logFile = dirname(__DIR__) . '/logs/api.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contextStr = !empty($context) ? json_encode($context) : '';
    
    $logEntry = "[$timestamp] [$level] IP:$ip - $message";
    if ($contextStr) {
        $logEntry .= " - Context: $contextStr";
    }
    $logEntry .= "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function logError($message, $context = [], $channel = 'error') {
    logAPI($message, $context, 'ERROR');
}

function logSecurity($message, $context = [], $level = 'INFO') {
    $logFile = dirname(__DIR__) . '/logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contextStr = !empty($context) ? json_encode($context) : '';
    
    $logEntry = "[$timestamp] [$level] IP:$ip - $message";
    if ($contextStr) {
        $logEntry .= " - Context: $contextStr";
    }
    $logEntry .= "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>