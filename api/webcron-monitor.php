<?php
/**
 * ===== WEBCRON MONITORING - SITE REMMAILLEUSE =====
 * Point d'entrée pour le monitoring via webcron (hébergement web)
 * À appeler depuis un service de webcron externe
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Configuration de sécurité pour les webcrons
$WEBCRON_CONFIG = [
    'secret_key' => 'remmailleuse_monitor_2025', // À changer en production
    'allowed_ips' => [
        // IPs des services de webcron populaires
        '46.4.84.', // EasyCron
        '52.', // AWS (utilisé par plusieurs services)
        '54.', // AWS
        '34.', // Google Cloud
        '104.', // Cloudflare
        '162.', // Cloudflare
        '172.', // Cloudflare
        '127.0.0.1', // Local testing
    ],
    'max_execution_time' => 30,
    'enable_ip_check' => true,
    'enable_key_check' => true,
    'log_requests' => true
];

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Inclure les dépendances
require_once 'Logger.php';

// Fonction de sécurité pour webcron
function validateWebcronAccess() {
    global $WEBCRON_CONFIG;
    
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $secretKey = $_GET['key'] ?? $_POST['key'] ?? '';
    
    // Log de la requête
    if ($WEBCRON_CONFIG['log_requests']) {
        logSecurity('Tentative accès webcron monitor', [
            'ip' => $clientIP,
            'user_agent' => $userAgent,
            'has_key' => !empty($secretKey),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ], 'INFO');
    }
    
    // Vérification de la clé secrète
    if ($WEBCRON_CONFIG['enable_key_check'] && $secretKey !== $WEBCRON_CONFIG['secret_key']) {
        logSecurity('Accès webcron refusé - clé invalide', [
            'ip' => $clientIP,
            'user_agent' => $userAgent,
            'provided_key' => substr($secretKey, 0, 10) . '...'
        ], 'WARNING');
        
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Clé d\'authentification invalide',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Vérification de l'IP (optionnelle mais recommandée)
    if ($WEBCRON_CONFIG['enable_ip_check']) {
        $ipAllowed = false;
        foreach ($WEBCRON_CONFIG['allowed_ips'] as $allowedPrefix) {
            if (strpos($clientIP, $allowedPrefix) === 0) {
                $ipAllowed = true;
                break;
            }
        }
        
        if (!$ipAllowed) {
            logSecurity('Accès webcron refusé - IP non autorisée', [
                'ip' => $clientIP,
                'user_agent' => $userAgent
            ], 'WARNING');
            
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'IP non autorisée',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
    }
    
    // Accès autorisé
    logSecurity('Accès webcron autorisé', [
        'ip' => $clientIP,
        'user_agent' => $userAgent
    ], 'INFO');
    
    return true;
}

// Fonction principale du monitoring
function runWebcronMonitoring() {
    global $WEBCRON_CONFIG;
    
    // Configurer le timeout
    set_time_limit($WEBCRON_CONFIG['max_execution_time']);
    
    $startTime = microtime(true);
    $monitoringResult = [
        'success' => false,
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time_ms' => 0,
        'monitoring_result' => null,
        'errors' => []
    ];
    
    try {
        // Exécuter le health check
        $healthResult = performHealthCheck();
        
        // Analyser les résultats
        $analysis = analyzeResults($healthResult);
        
        // Sauvegarder le statut
        saveWebcronStatus($analysis);
        
        // Gérer les alertes
        handleWebcronAlerts($analysis);
        
        // Succès
        $monitoringResult['success'] = true;
        $monitoringResult['monitoring_result'] = $analysis;
        
        // Log du succès
        logAPI('Webcron monitoring exécuté avec succès', [
            'status' => $analysis['status'],
            'health_score' => $analysis['metrics']['health_score'] ?? 0,
            'alerts_count' => count($analysis['alerts']),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'INFO');
        
    } catch (Exception $e) {
        $monitoringResult['errors'][] = $e->getMessage();
        
        // Log de l'erreur
        logError('Erreur webcron monitoring', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'performance');
    }
    
    // Calculer le temps d'exécution
    $monitoringResult['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
    
    return $monitoringResult;
}

// Exécuter le health check en interne
function performHealthCheck() {
    // Capturer la sortie du health check
    ob_start();
    
    // Simuler l'inclusion du health check
    $_SERVER['REQUEST_METHOD'] = 'GET'; // Forcer GET pour éviter les validations CSRF
    
    try {
        // Inclure le health check
        include 'health-check.php';
        $output = ob_get_clean();
        
        // Décoder le JSON
        $result = json_decode($output, true);
        
        if (!$result) {
            throw new Exception('Réponse health check invalide');
        }
        
        return $result;
        
    } catch (Exception $e) {
        ob_end_clean();
        throw new Exception('Erreur health check: ' . $e->getMessage());
    }
}

// Analyser les résultats du health check
function analyzeResults($healthResult) {
    $analysis = [
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'status' => $healthResult['status'] ?? 'unknown',
        'execution_time_ms' => $healthResult['execution_time_ms'] ?? 0,
        'alerts' => $healthResult['alerts'] ?? [],
        'checks' => $healthResult['checks'] ?? [],
        'metrics' => calculateWebcronMetrics($healthResult),
        'recommendations' => generateWebcronRecommendations($healthResult),
        'should_alert' => false
    ];
    
    // Déterminer si des alertes sont nécessaires
    $criticalLevels = ['critical', 'error'];
    foreach ($analysis['alerts'] as $alert) {
        if (in_array($alert['level'], $criticalLevels)) {
            $analysis['should_alert'] = true;
            break;
        }
    }
    
    return $analysis;
}

// Calculer les métriques pour webcron
function calculateWebcronMetrics($healthResult) {
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
    
    if ($metrics['total_checks'] > 0) {
        $metrics['health_score'] = round(
            ($metrics['healthy_checks'] / $metrics['total_checks']) * 100
        );
    }
    
    return $metrics;
}

// Générer des recommandations pour webcron
function generateWebcronRecommendations($healthResult) {
    $recommendations = [];
    
    // Analyser les checks
    foreach ($healthResult['checks'] as $category => $check) {
        if ($check['status'] !== 'healthy') {
            switch ($category) {
                case 'system':
                    $recommendations[] = "Vérifier les ressources système ($category)";
                    break;
                case 'cache':
                    $recommendations[] = "Optimiser la configuration du cache";
                    break;
                case 'logs':
                    $recommendations[] = "Nettoyer ou configurer la rotation des logs";
                    break;
                case 'security':
                    $recommendations[] = "Revoir la configuration de sécurité";
                    break;
                default:
                    $recommendations[] = "Vérifier le composant $category";
            }
        }
    }
    
    return $recommendations;
}

// Sauvegarder le statut pour webcron
function saveWebcronStatus($analysis) {
    try {
        $statusFile = '../temp/webcron_status.json';
        
        // Créer le répertoire si nécessaire
        $statusDir = dirname($statusFile);
        if (!is_dir($statusDir)) {
            mkdir($statusDir, 0755, true);
        }
        
        // Charger l'historique existant
        $history = [];
        if (file_exists($statusFile)) {
            $existingData = json_decode(file_get_contents($statusFile), true);
            $history = $existingData['history'] ?? [];
        }
        
        // Ajouter l'entrée actuelle
        $history[] = [
            'timestamp' => $analysis['timestamp'],
            'status' => $analysis['status'],
            'health_score' => $analysis['metrics']['health_score'],
            'alerts_count' => count($analysis['alerts']),
            'execution_time_ms' => $analysis['execution_time_ms']
        ];
        
        // Garder seulement les 50 dernières entrées (pour limiter la taille)
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        // Sauvegarder
        $statusData = [
            'last_check' => $analysis,
            'history' => $history,
            'updated' => date('Y-m-d H:i:s'),
            'webcron_info' => [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        ];
        
        file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
        
    } catch (Exception $e) {
        logError('Erreur sauvegarde statut webcron', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'performance');
    }
}

// Gérer les alertes pour webcron
function handleWebcronAlerts($analysis) {
    if (!$analysis['should_alert']) {
        return;
    }
    
    try {
        // Vérifier le cooldown
        $cooldownFile = '../temp/webcron_last_alert.txt';
        $cooldownTime = 900; // 15 minutes entre les alertes
        
        if (file_exists($cooldownFile)) {
            $lastAlert = (int) file_get_contents($cooldownFile);
            if ((time() - $lastAlert) < $cooldownTime) {
                return; // Encore en cooldown
            }
        }
        
        // Envoyer l'alerte
        require_once 'EmailManager.php';
        $emailManager = new EmailManager();
        
        $subject = "🚨 ALERTE WEBCRON - Site ReMmailleuse";
        $message = buildWebcronAlertMessage($analysis);
        
        $emailManager->sendAdminAlert($subject, $message);
        
        // Enregistrer le temps d'alerte
        file_put_contents($cooldownFile, time());
        
        // Log de l'alerte
        logSecurity('Alerte webcron envoyée', [
            'status' => $analysis['status'],
            'alerts_count' => count($analysis['alerts']),
            'health_score' => $analysis['metrics']['health_score']
        ], 'WARNING');
        
    } catch (Exception $e) {
        logError('Erreur envoi alerte webcron', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'email');
    }
}

// Construire le message d'alerte pour webcron
function buildWebcronAlertMessage($analysis) {
    $message = "🚨 ALERTE WEBCRON MONITORING\n\n";
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
        foreach ($analysis['recommendations'] as $rec) {
            $message .= "- $rec\n";
        }
        $message .= "\n";
    }
    
    $message .= "📊 MÉTRIQUES:\n";
    $message .= "- Vérifications: {$analysis['metrics']['total_checks']}\n";
    $message .= "- Saines: {$analysis['metrics']['healthy_checks']}\n";
    $message .= "- Alertes: {$analysis['metrics']['warning_checks']}\n";
    $message .= "- Critiques: {$analysis['metrics']['critical_checks']}\n";
    $message .= "- Erreurs: {$analysis['metrics']['error_checks']}\n\n";
    
    $message .= "🔗 Surveillance via webcron activée\n";
    
    return $message;
}

// === POINT D'ENTRÉE PRINCIPAL ===

try {
    // Valider l'accès webcron
    validateWebcronAccess();
    
    // Exécuter le monitoring
    $result = runWebcronMonitoring();
    
    // Retourner le résultat
    echo json_encode($result);
    
} catch (Exception $e) {
    // Erreur générale
    logError('Erreur générale webcron monitor', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'performance');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne du monitoring',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

?>