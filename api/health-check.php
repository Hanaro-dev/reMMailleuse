<?php
/**
 * ===== HEALTH CHECK ENDPOINT - SITE REMMAILLEUSE =====
 * Point de contrôle de santé du système pour monitoring
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Inclure les dépendances
require_once 'Logger.php';
require_once 'CacheManager.php';
require_once 'DatabaseManager.php';
require_once 'RateLimiter.php';

// Configuration
$CONFIG = [
    'timeout' => 30, // 30 secondes maximum
    'critical_thresholds' => [
        'disk_space' => 90, // 90% d'utilisation disque
        'memory' => 80,     // 80% d'utilisation mémoire
        'load' => 5.0       // Load average maximum
    ],
    'warning_thresholds' => [
        'disk_space' => 70,
        'memory' => 60,
        'load' => 3.0
    ]
];

// Timeout global
set_time_limit($CONFIG['timeout']);

try {
    $startTime = microtime(true);
    $healthStatus = 'healthy';
    $checks = [];
    $alerts = [];
    
    // 1. Vérification PHP
    $checks['php'] = checkPHP();
    
    // 2. Vérification système de fichiers
    $checks['filesystem'] = checkFilesystem();
    
    // 3. Vérification permissions
    $checks['permissions'] = checkPermissions();
    
    // 4. Vérification base de données (si applicable)
    $checks['database'] = checkDatabase();
    
    // 5. Vérification cache
    $checks['cache'] = checkCache();
    
    // 6. Vérification logs
    $checks['logs'] = checkLogs();
    
    // 7. Vérification rate limiting
    $checks['rate_limiting'] = checkRateLimiting();
    
    // 8. Vérification système (CPU, mémoire, disque)
    $checks['system'] = checkSystem();
    
    // 9. Vérification APIs critiques
    $checks['apis'] = checkAPIs();
    
    // 10. Vérification sécurité
    $checks['security'] = checkSecurity();
    
    // Calculer le statut global
    $healthStatus = calculateOverallStatus($checks);
    
    // Générer les alertes
    $alerts = generateAlerts($checks, $CONFIG);
    
    // Temps d'exécution
    $executionTime = round((microtime(true) - $startTime) * 1000, 2); // en ms
    
    // Préparer la réponse
    $response = [
        'status' => $healthStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time_ms' => $executionTime,
        'checks' => $checks,
        'alerts' => $alerts,
        'system_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'load_average' => getLoadAverage()
        ]
    ];
    
    // Log du health check
    logAPI('Health check exécuté', [
        'status' => $healthStatus,
        'execution_time_ms' => $executionTime,
        'alerts_count' => count($alerts),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'INFO');
    
    // Code de statut HTTP selon le résultat
    switch ($healthStatus) {
        case 'healthy':
            http_response_code(200);
            break;
        case 'warning':
            http_response_code(200); // OK mais avec warnings
            break;
        case 'critical':
            http_response_code(503); // Service Unavailable
            break;
        case 'error':
            http_response_code(500); // Internal Server Error
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log erreur critique
    logError('Erreur critique dans health check', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 'performance');
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => 'Health check failed: ' . $e->getMessage()
    ]);
}

/**
 * Vérification PHP
 */
function checkPHP() {
    $check = [
        'status' => 'healthy',
        'message' => 'PHP fonctionne correctement',
        'details' => [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ]
    ];
    
    // Vérifier les extensions critiques
    $requiredExtensions = ['json', 'gd', 'curl', 'openssl'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (!empty($missingExtensions)) {
        $check['status'] = 'critical';
        $check['message'] = 'Extensions PHP manquantes: ' . implode(', ', $missingExtensions);
        $check['details']['missing_extensions'] = $missingExtensions;
    }
    
    return $check;
}

/**
 * Vérification système de fichiers
 */
function checkFilesystem() {
    $check = [
        'status' => 'healthy',
        'message' => 'Système de fichiers accessible',
        'details' => []
    ];
    
    $directories = [
        'uploads' => '../uploads/',
        'cache' => '../cache/',
        'logs' => '../logs/',
        'data' => '../data/',
        'temp' => '../temp/'
    ];
    
    foreach ($directories as $name => $path) {
        if (!is_dir($path)) {
            $check['details'][$name] = 'missing';
            if ($check['status'] === 'healthy') {
                $check['status'] = 'warning';
                $check['message'] = 'Certains répertoires manquent';
            }
        } else {
            $check['details'][$name] = 'exists';
        }
    }
    
    return $check;
}

/**
 * Vérification permissions
 */
function checkPermissions() {
    $check = [
        'status' => 'healthy',
        'message' => 'Permissions correctes',
        'details' => []
    ];
    
    $writablePaths = [
        'uploads' => '../uploads/',
        'cache' => '../cache/',
        'logs' => '../logs/',
        'temp' => '../temp/'
    ];
    
    foreach ($writablePaths as $name => $path) {
        if (is_dir($path)) {
            $writable = is_writable($path);
            $check['details'][$name] = $writable ? 'writable' : 'not_writable';
            
            if (!$writable) {
                $check['status'] = 'critical';
                $check['message'] = 'Permissions insuffisantes';
            }
        }
    }
    
    return $check;
}

/**
 * Vérification base de données
 */
function checkDatabase() {
    $check = [
        'status' => 'healthy',
        'message' => 'Base de données non utilisée',
        'details' => ['type' => 'file_based']
    ];
    
    // Pour cette application, on utilise des fichiers JSON
    // Vérifier la disponibilité des fichiers de données
    $dataFiles = [
        'settings' => '../data/settings.json'
    ];
    
    foreach ($dataFiles as $name => $file) {
        if (file_exists($file)) {
            $check['details'][$name] = 'available';
        } else {
            $check['details'][$name] = 'missing';
            $check['status'] = 'warning';
            $check['message'] = 'Fichiers de données manquants';
        }
    }
    
    return $check;
}

/**
 * Vérification cache
 */
function checkCache() {
    $check = [
        'status' => 'healthy',
        'message' => 'Cache opérationnel',
        'details' => []
    ];
    
    try {
        $cacheManager = new CacheManager();
        $testKey = 'health_check_' . time();
        $testValue = 'test_value';
        
        // Test écriture
        $cacheManager->set($testKey, $testValue, 60);
        
        // Test lecture
        $retrieved = $cacheManager->get($testKey);
        
        if ($retrieved === $testValue) {
            $check['details']['read_write'] = 'ok';
        } else {
            $check['status'] = 'warning';
            $check['message'] = 'Cache lecture/écriture défaillant';
            $check['details']['read_write'] = 'failed';
        }
        
        // Nettoyage
        $cacheManager->delete($testKey);
        
        // Statistiques
        $stats = $cacheManager->getStats();
        $check['details']['stats'] = $stats;
        
    } catch (Exception $e) {
        $check['status'] = 'error';
        $check['message'] = 'Erreur cache: ' . $e->getMessage();
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Vérification logs
 */
function checkLogs() {
    $check = [
        'status' => 'healthy',
        'message' => 'Système de logs fonctionnel',
        'details' => []
    ];
    
    try {
        $logger = getLogger();
        
        // Test d'écriture
        $logger->info('Health check test log', ['test' => true], 'debug');
        
        // Vérifier les statistiques
        $stats = $logger->getStats();
        $check['details']['stats'] = $stats;
        
        // Vérifier l'espace disque des logs
        $totalSize = $stats['total_size'] ?? 0;
        $maxSize = 100 * 1024 * 1024; // 100MB maximum
        
        if ($totalSize > $maxSize) {
            $check['status'] = 'warning';
            $check['message'] = 'Logs volumineux, rotation recommandée';
        }
        
    } catch (Exception $e) {
        $check['status'] = 'error';
        $check['message'] = 'Erreur logging: ' . $e->getMessage();
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Vérification rate limiting
 */
function checkRateLimiting() {
    $check = [
        'status' => 'healthy',
        'message' => 'Rate limiting opérationnel',
        'details' => []
    ];
    
    try {
        $rateLimiter = getRateLimiter();
        $testIP = '127.0.0.1';
        
        // Test avec IP de test
        $result = $rateLimiter->isAllowed($testIP, 'general', ['test' => true]);
        
        if ($result['allowed']) {
            $check['details']['test_request'] = 'allowed';
        } else {
            $check['details']['test_request'] = 'blocked';
        }
        
        // Statistiques
        $stats = $rateLimiter->getStats();
        $check['details']['stats'] = [
            'total_requests' => $stats['global']['total_requests'] ?? 0,
            'blocked_requests' => $stats['global']['blocked_requests'] ?? 0
        ];
        
    } catch (Exception $e) {
        $check['status'] = 'error';
        $check['message'] = 'Erreur rate limiting: ' . $e->getMessage();
        $check['details']['error'] = $e->getMessage();
    }
    
    return $check;
}

/**
 * Vérification système
 */
function checkSystem() {
    $check = [
        'status' => 'healthy',
        'message' => 'Système en bon état',
        'details' => []
    ];
    
    // Mémoire
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
    
    $check['details']['memory'] = [
        'usage' => formatBytes($memoryUsage),
        'limit' => $memoryLimit,
        'percent' => round($memoryPercent, 2)
    ];
    
    if ($memoryPercent > 80) {
        $check['status'] = 'critical';
        $check['message'] = 'Utilisation mémoire élevée';
    } elseif ($memoryPercent > 60) {
        $check['status'] = 'warning';
        $check['message'] = 'Utilisation mémoire modérée';
    }
    
    // Espace disque
    $diskFree = disk_free_space('.');
    $diskTotal = disk_total_space('.');
    $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
    
    $check['details']['disk'] = [
        'free' => formatBytes($diskFree),
        'total' => formatBytes($diskTotal),
        'used_percent' => round($diskUsedPercent, 2)
    ];
    
    if ($diskUsedPercent > 90) {
        $check['status'] = 'critical';
        $check['message'] = 'Espace disque critique';
    } elseif ($diskUsedPercent > 70) {
        if ($check['status'] === 'healthy') {
            $check['status'] = 'warning';
            $check['message'] = 'Espace disque limité';
        }
    }
    
    return $check;
}

/**
 * Vérification APIs critiques
 */
function checkAPIs() {
    $check = [
        'status' => 'healthy',
        'message' => 'APIs accessibles',
        'details' => []
    ];
    
    $apis = [
        'contact' => 'contact.php',
        'auth' => 'auth.php',
        'upload' => 'upload.php',
        'backup' => 'backup.php'
    ];
    
    foreach ($apis as $name => $file) {
        if (file_exists($file)) {
            $check['details'][$name] = 'available';
        } else {
            $check['details'][$name] = 'missing';
            $check['status'] = 'critical';
            $check['message'] = 'APIs critiques manquantes';
        }
    }
    
    return $check;
}

/**
 * Vérification sécurité
 */
function checkSecurity() {
    $check = [
        'status' => 'healthy',
        'message' => 'Configuration sécurisée',
        'details' => []
    ];
    
    // Vérifier les paramètres PHP critiques
    $securitySettings = [
        'display_errors' => ini_get('display_errors'),
        'expose_php' => ini_get('expose_php'),
        'allow_url_fopen' => ini_get('allow_url_fopen'),
        'allow_url_include' => ini_get('allow_url_include')
    ];
    
    $check['details']['php_settings'] = $securitySettings;
    
    // Recommandations sécurité
    if ($securitySettings['display_errors'] === '1') {
        $check['status'] = 'warning';
        $check['message'] = 'display_errors activé en production';
    }
    
    if ($securitySettings['expose_php'] === '1') {
        if ($check['status'] === 'healthy') {
            $check['status'] = 'warning';
            $check['message'] = 'expose_php activé';
        }
    }
    
    return $check;
}

/**
 * Calculer le statut global
 */
function calculateOverallStatus($checks) {
    $hasError = false;
    $hasCritical = false;
    $hasWarning = false;
    
    foreach ($checks as $check) {
        switch ($check['status']) {
            case 'error':
                $hasError = true;
                break;
            case 'critical':
                $hasCritical = true;
                break;
            case 'warning':
                $hasWarning = true;
                break;
        }
    }
    
    if ($hasError) {
        return 'error';
    } elseif ($hasCritical) {
        return 'critical';
    } elseif ($hasWarning) {
        return 'warning';
    } else {
        return 'healthy';
    }
}

/**
 * Générer les alertes
 */
function generateAlerts($checks, $config) {
    $alerts = [];
    
    foreach ($checks as $category => $check) {
        if ($check['status'] !== 'healthy') {
            $alerts[] = [
                'category' => $category,
                'level' => $check['status'],
                'message' => $check['message'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    return $alerts;
}

/**
 * Obtenir le load average
 */
function getLoadAverage() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return $load[0]; // 1 minute load average
    }
    return null;
}

/**
 * Convertir une valeur en bytes
 */
function convertToBytes($value) {
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $number = (int) substr($value, 0, -1);
    
    switch ($unit) {
        case 'g':
            $number *= 1024;
        case 'm':
            $number *= 1024;
        case 'k':
            $number *= 1024;
    }
    
    return $number;
}

/**
 * Formater les octets
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

?>