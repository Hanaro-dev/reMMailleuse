<?php
/**
 * ===== SCRIPT DE MONITORING - SITE REMMAILLEUSE =====
 * Script de monitoring automatique utilisant le health check
 * Peut être exécuté via cron pour surveillance continue
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Configuration du monitoring
$MONITORING_CONFIG = [
    'health_check_url' => 'http://localhost/api/health-check.php',
    'alert_email' => 'admin@remmailleuse.ch',
    'alert_levels' => ['critical', 'error'],
    'log_file' => __DIR__ . '/../logs/monitoring.log',
    'status_file' => __DIR__ . '/../temp/monitoring_status.json',
    'alert_cooldown' => 300, // 5 minutes entre les alertes
    'max_retries' => 3,
    'retry_delay' => 10 // secondes
];

// Inclure le système de logging
require_once __DIR__ . '/../api/Logger.php';
require_once __DIR__ . '/../api/EmailManager.php';

// Fonction principale
function main() {
    global $MONITORING_CONFIG;
    
    try {
        // Créer les répertoires nécessaires
        createDirectories();
        
        // Exécuter le health check
        $healthResult = performHealthCheck();
        
        // Analyser les résultats
        $analysis = analyzeHealthCheck($healthResult);
        
        // Enregistrer le statut
        saveStatus($analysis);
        
        // Envoyer des alertes si nécessaire
        handleAlerts($analysis);
        
        // Log du monitoring
        logMonitoring($analysis);
        
        // Output pour cron
        echo "Monitoring completed - Status: {$analysis['status']}\n";
        
        // Code de sortie
        exit($analysis['status'] === 'healthy' ? 0 : 1);
        
    } catch (Exception $e) {
        // Log erreur critique
        logError('Erreur critique dans le monitoring', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'performance');
        
        echo "Monitoring failed: " . $e->getMessage() . "\n";
        exit(2);
    }
}

/**
 * Créer les répertoires nécessaires
 */
function createDirectories() {
    $dirs = [
        dirname($GLOBALS['MONITORING_CONFIG']['log_file']),
        dirname($GLOBALS['MONITORING_CONFIG']['status_file'])
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Exécuter le health check
 */
function performHealthCheck() {
    global $MONITORING_CONFIG;
    
    $attempts = 0;
    $lastError = null;
    
    while ($attempts < $MONITORING_CONFIG['max_retries']) {
        try {
            $attempts++;
            
            // Initialiser cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $MONITORING_CONFIG['health_check_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ReMmailleuse-Monitor/1.0');
            
            // Exécuter la requête
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Vérifier les erreurs cURL
            if ($error) {
                throw new Exception("Erreur cURL: $error");
            }
            
            // Vérifier le code HTTP
            if ($httpCode !== 200 && $httpCode !== 503) {
                throw new Exception("Code HTTP inattendu: $httpCode");
            }
            
            // Décoder la réponse JSON
            $result = json_decode($response, true);
            if (!$result) {
                throw new Exception("Réponse JSON invalide");
            }
            
            // Ajouter les métadonnées
            $result['http_code'] = $httpCode;
            $result['response_time'] = time();
            $result['attempt'] = $attempts;
            
            return $result;
            
        } catch (Exception $e) {
            $lastError = $e;
            
            if ($attempts < $MONITORING_CONFIG['max_retries']) {
                sleep($MONITORING_CONFIG['retry_delay']);
            }
        }
    }
    
    // Tous les essais ont échoué
    throw new Exception("Health check failed after {$MONITORING_CONFIG['max_retries']} attempts: " . $lastError->getMessage());
}

/**
 * Analyser les résultats du health check
 */
function analyzeHealthCheck($healthResult) {
    $analysis = [
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'status' => $healthResult['status'],
        'http_code' => $healthResult['http_code'],
        'execution_time_ms' => $healthResult['execution_time_ms'] ?? 0,
        'alerts' => $healthResult['alerts'] ?? [],
        'checks' => $healthResult['checks'] ?? [],
        'recommendations' => [],
        'should_alert' => false
    ];
    
    // Analyser les alertes
    if (!empty($analysis['alerts'])) {
        foreach ($analysis['alerts'] as $alert) {
            if (in_array($alert['level'], $GLOBALS['MONITORING_CONFIG']['alert_levels'])) {
                $analysis['should_alert'] = true;
                break;
            }
        }
    }
    
    // Générer des recommandations
    $analysis['recommendations'] = generateRecommendations($analysis);
    
    // Calculer des métriques
    $analysis['metrics'] = calculateMetrics($healthResult);
    
    return $analysis;
}

/**
 * Générer des recommandations
 */
function generateRecommendations($analysis) {
    $recommendations = [];
    
    // Analyser les checks individuels
    foreach ($analysis['checks'] as $category => $check) {
        switch ($category) {
            case 'system':
                if (isset($check['details']['memory']['percent']) && $check['details']['memory']['percent'] > 80) {
                    $recommendations[] = 'Optimiser l\'utilisation mémoire ou augmenter la limite';
                }
                if (isset($check['details']['disk']['used_percent']) && $check['details']['disk']['used_percent'] > 80) {
                    $recommendations[] = 'Nettoyer l\'espace disque ou augmenter la capacité';
                }
                break;
                
            case 'logs':
                if (isset($check['details']['stats']['total_size']) && $check['details']['stats']['total_size'] > 50 * 1024 * 1024) {
                    $recommendations[] = 'Configurer la rotation des logs';
                }
                break;
                
            case 'cache':
                if ($check['status'] !== 'healthy') {
                    $recommendations[] = 'Vérifier la configuration du cache';
                }
                break;
                
            case 'security':
                if ($check['status'] !== 'healthy') {
                    $recommendations[] = 'Revoir la configuration de sécurité PHP';
                }
                break;
        }
    }
    
    // Temps d'exécution
    if ($analysis['execution_time_ms'] > 5000) {
        $recommendations[] = 'Optimiser les performances du health check';
    }
    
    return $recommendations;
}

/**
 * Calculer des métriques
 */
function calculateMetrics($healthResult) {
    $metrics = [
        'total_checks' => count($healthResult['checks'] ?? []),
        'healthy_checks' => 0,
        'warning_checks' => 0,
        'critical_checks' => 0,
        'error_checks' => 0,
        'health_score' => 0
    ];
    
    foreach ($healthResult['checks'] as $check) {
        switch ($check['status']) {
            case 'healthy':
                $metrics['healthy_checks']++;
                break;
            case 'warning':
                $metrics['warning_checks']++;
                break;
            case 'critical':
                $metrics['critical_checks']++;
                break;
            case 'error':
                $metrics['error_checks']++;
                break;
        }
    }
    
    // Calculer le score de santé (0-100)
    if ($metrics['total_checks'] > 0) {
        $metrics['health_score'] = round(
            ($metrics['healthy_checks'] / $metrics['total_checks']) * 100
        );
    }
    
    return $metrics;
}

/**
 * Sauvegarder le statut
 */
function saveStatus($analysis) {
    global $MONITORING_CONFIG;
    
    try {
        // Charger l'historique existant
        $history = [];
        if (file_exists($MONITORING_CONFIG['status_file'])) {
            $content = file_get_contents($MONITORING_CONFIG['status_file']);
            $data = json_decode($content, true);
            $history = $data['history'] ?? [];
        }
        
        // Ajouter le nouveau statut
        $history[] = [
            'timestamp' => $analysis['timestamp'],
            'status' => $analysis['status'],
            'health_score' => $analysis['metrics']['health_score'],
            'alerts_count' => count($analysis['alerts']),
            'execution_time_ms' => $analysis['execution_time_ms']
        ];
        
        // Garder seulement les 100 dernières entrées
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }
        
        // Préparer les données complètes
        $statusData = [
            'last_check' => $analysis,
            'history' => $history,
            'updated' => date('Y-m-d H:i:s')
        ];
        
        // Sauvegarder
        file_put_contents(
            $MONITORING_CONFIG['status_file'],
            json_encode($statusData, JSON_PRETTY_PRINT)
        );
        
    } catch (Exception $e) {
        logError('Erreur sauvegarde statut monitoring', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'performance');
    }
}

/**
 * Gérer les alertes
 */
function handleAlerts($analysis) {
    global $MONITORING_CONFIG;
    
    if (!$analysis['should_alert']) {
        return;
    }
    
    try {
        // Vérifier le cooldown
        if (isInCooldown()) {
            return;
        }
        
        // Envoyer l'alerte
        sendAlert($analysis);
        
        // Enregistrer le temps d'alerte
        recordAlertTime();
        
    } catch (Exception $e) {
        logError('Erreur gestion alertes monitoring', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'performance');
    }
}

/**
 * Vérifier le cooldown des alertes
 */
function isInCooldown() {
    global $MONITORING_CONFIG;
    
    $cooldownFile = dirname($MONITORING_CONFIG['status_file']) . '/last_alert.txt';
    
    if (!file_exists($cooldownFile)) {
        return false;
    }
    
    $lastAlert = (int) file_get_contents($cooldownFile);
    $now = time();
    
    return ($now - $lastAlert) < $MONITORING_CONFIG['alert_cooldown'];
}

/**
 * Enregistrer le temps d'alerte
 */
function recordAlertTime() {
    global $MONITORING_CONFIG;
    
    $cooldownFile = dirname($MONITORING_CONFIG['status_file']) . '/last_alert.txt';
    file_put_contents($cooldownFile, time());
}

/**
 * Envoyer une alerte
 */
function sendAlert($analysis) {
    global $MONITORING_CONFIG;
    
    try {
        $emailManager = new EmailManager();
        
        $subject = "🚨 ALERTE MONITORING - Site ReMmailleuse";
        $message = buildAlertMessage($analysis);
        
        // Envoyer l'alerte
        $emailManager->sendAdminAlert($subject, $message);
        
        // Log de l'alerte
        logAPI('Alerte monitoring envoyée', [
            'status' => $analysis['status'],
            'alerts_count' => count($analysis['alerts']),
            'health_score' => $analysis['metrics']['health_score']
        ], 'WARNING');
        
    } catch (Exception $e) {
        logError('Erreur envoi alerte monitoring', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'performance');
    }
}

/**
 * Construire le message d'alerte
 */
function buildAlertMessage($analysis) {
    $message = "🚨 ALERTE MONITORING SYSTÈME\n\n";
    $message .= "Statut: " . strtoupper($analysis['status']) . "\n";
    $message .= "Score de santé: {$analysis['metrics']['health_score']}/100\n";
    $message .= "Timestamp: {$analysis['datetime']}\n";
    $message .= "Temps d'exécution: {$analysis['execution_time_ms']}ms\n\n";
    
    if (!empty($analysis['alerts'])) {
        $message .= "🚨 ALERTES DÉTECTÉES:\n";
        foreach ($analysis['alerts'] as $alert) {
            $message .= "- [{$alert['level']}] {$alert['category']}: {$alert['message']}\n";
        }
        $message .= "\n";
    }
    
    if (!empty($analysis['recommendations'])) {
        $message .= "💡 RECOMMANDATIONS:\n";
        foreach ($analysis['recommendations'] as $recommendation) {
            $message .= "- $recommendation\n";
        }
        $message .= "\n";
    }
    
    // Métriques détaillées
    $message .= "📊 MÉTRIQUES:\n";
    $message .= "- Vérifications totales: {$analysis['metrics']['total_checks']}\n";
    $message .= "- Saines: {$analysis['metrics']['healthy_checks']}\n";
    $message .= "- Avertissements: {$analysis['metrics']['warning_checks']}\n";
    $message .= "- Critiques: {$analysis['metrics']['critical_checks']}\n";
    $message .= "- Erreurs: {$analysis['metrics']['error_checks']}\n\n";
    
    $message .= "🔗 Vérifiez le health check: " . $GLOBALS['MONITORING_CONFIG']['health_check_url'] . "\n";
    
    return $message;
}

/**
 * Logger les événements de monitoring
 */
function logMonitoring($analysis) {
    try {
        logPerformance('Monitoring exécuté', [
            'status' => $analysis['status'],
            'execution_time_ms' => $analysis['execution_time_ms'],
            'health_score' => $analysis['metrics']['health_score'],
            'alerts_count' => count($analysis['alerts']),
            'recommendations_count' => count($analysis['recommendations'])
        ], 'INFO');
        
    } catch (Exception $e) {
        // Log basique si le système centralisé échoue
        error_log("Monitoring: {$analysis['status']} - {$e->getMessage()}");
    }
}

// Exécuter le monitoring si ce script est appelé directement
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'monitor.php') {
    main();
}

?>