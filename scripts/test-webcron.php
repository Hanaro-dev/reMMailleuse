<?php
/**
 * ===== TEST WEBCRON MONITORING - SITE REMMAILLEUSE =====
 * Script pour tester le monitoring webcron en local
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Configuration du test
$TEST_CONFIG = [
    'webcron_url' => 'http://localhost/api/webcron-monitor.php',
    'secret_key' => 'remmailleuse_monitor_2025', // Même clé que dans webcron-monitor.php
    'timeout' => 30,
    'verbose' => true
];

// Couleurs pour l'affichage
$COLORS = [
    'green' => "\033[0;32m",
    'red' => "\033[0;31m",
    'yellow' => "\033[1;33m",
    'blue' => "\033[0;34m",
    'reset' => "\033[0m"
];

// Fonction d'affichage coloré
function coloredOutput($text, $color = 'reset') {
    global $COLORS;
    if (php_sapi_name() === 'cli') {
        echo $COLORS[$color] . $text . $COLORS['reset'] . "\n";
    } else {
        echo "<div style='color: " . $color . ";'>" . htmlspecialchars($text) . "</div>\n";
    }
}

// Fonction de test principal
function testWebcron() {
    global $TEST_CONFIG;
    
    coloredOutput("=== TEST WEBCRON MONITORING ===", 'blue');
    coloredOutput("URL: " . $TEST_CONFIG['webcron_url'], 'blue');
    coloredOutput("", 'reset');
    
    // 1. Test sans clé
    coloredOutput("1. Test sans clé (doit échouer):", 'yellow');
    $result1 = makeWebcronRequest($TEST_CONFIG['webcron_url']);
    displayResult($result1, false);
    
    // 2. Test avec clé invalide
    coloredOutput("2. Test avec clé invalide (doit échouer):", 'yellow');
    $result2 = makeWebcronRequest($TEST_CONFIG['webcron_url'] . '?key=invalid_key');
    displayResult($result2, false);
    
    // 3. Test avec clé valide
    coloredOutput("3. Test avec clé valide (doit réussir):", 'yellow');
    $result3 = makeWebcronRequest($TEST_CONFIG['webcron_url'] . '?key=' . $TEST_CONFIG['secret_key']);
    displayResult($result3, true);
    
    // 4. Test de performance
    coloredOutput("4. Test de performance (5 requêtes):", 'yellow');
    testPerformance();
    
    // 5. Vérification des fichiers générés
    coloredOutput("5. Vérification des fichiers générés:", 'yellow');
    checkGeneratedFiles();
    
    coloredOutput("=== FIN DU TEST ===", 'blue');
}

// Faire une requête webcron
function makeWebcronRequest($url) {
    global $TEST_CONFIG;
    
    $startTime = microtime(true);
    
    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $TEST_CONFIG['timeout']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WebcronTest/1.0');
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $endTime = microtime(true);
    
    return [
        'success' => !$error && $response !== false,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'execution_time' => round(($endTime - $startTime) * 1000, 2),
        'info' => $info
    ];
}

// Afficher le résultat
function displayResult($result, $expectedSuccess) {
    global $TEST_CONFIG;
    
    $actualSuccess = $result['success'] && $result['http_code'] === 200;
    $testPassed = $expectedSuccess ? $actualSuccess : !$actualSuccess;
    
    // Statut du test
    if ($testPassed) {
        coloredOutput("  ✅ TEST RÉUSSI", 'green');
    } else {
        coloredOutput("  ❌ TEST ÉCHOUÉ", 'red');
    }
    
    // Détails
    coloredOutput("  Code HTTP: " . $result['http_code'], 'reset');
    coloredOutput("  Temps: " . $result['execution_time'] . "ms", 'reset');
    
    if ($result['error']) {
        coloredOutput("  Erreur cURL: " . $result['error'], 'red');
    }
    
    if ($result['response'] && $TEST_CONFIG['verbose']) {
        // Décoder et afficher la réponse JSON
        $jsonResponse = json_decode($result['response'], true);
        if ($jsonResponse) {
            coloredOutput("  Réponse JSON:", 'reset');
            if (isset($jsonResponse['success'])) {
                coloredOutput("    Success: " . ($jsonResponse['success'] ? 'true' : 'false'), 'reset');
            }
            if (isset($jsonResponse['error'])) {
                coloredOutput("    Error: " . $jsonResponse['error'], 'red');
            }
            if (isset($jsonResponse['monitoring_result']['status'])) {
                coloredOutput("    Status: " . $jsonResponse['monitoring_result']['status'], 'reset');
            }
            if (isset($jsonResponse['monitoring_result']['metrics']['health_score'])) {
                coloredOutput("    Health Score: " . $jsonResponse['monitoring_result']['metrics']['health_score'] . "/100", 'reset');
            }
        } else {
            coloredOutput("  Réponse brute: " . substr($result['response'], 0, 200) . "...", 'reset');
        }
    }
    
    coloredOutput("", 'reset');
}

// Test de performance
function testPerformance() {
    global $TEST_CONFIG;
    
    $times = [];
    $url = $TEST_CONFIG['webcron_url'] . '?key=' . $TEST_CONFIG['secret_key'];
    
    for ($i = 1; $i <= 5; $i++) {
        coloredOutput("  Test $i/5...", 'reset');
        $result = makeWebcronRequest($url);
        $times[] = $result['execution_time'];
        
        if (!$result['success'] || $result['http_code'] !== 200) {
            coloredOutput("    ❌ Échec: " . ($result['error'] ?: 'HTTP ' . $result['http_code']), 'red');
        } else {
            coloredOutput("    ✅ Réussi: " . $result['execution_time'] . "ms", 'green');
        }
        
        // Pause courte entre les tests
        usleep(500000); // 0.5 seconde
    }
    
    // Statistiques
    $avgTime = array_sum($times) / count($times);
    $minTime = min($times);
    $maxTime = max($times);
    
    coloredOutput("  Statistiques:", 'blue');
    coloredOutput("    Temps moyen: " . round($avgTime, 2) . "ms", 'reset');
    coloredOutput("    Temps min: " . $minTime . "ms", 'reset');
    coloredOutput("    Temps max: " . $maxTime . "ms", 'reset');
    
    if ($avgTime > 10000) {
        coloredOutput("    ⚠️ Performance dégradée (>10s)", 'yellow');
    } elseif ($avgTime > 5000) {
        coloredOutput("    ⚠️ Performance acceptable (5-10s)", 'yellow');
    } else {
        coloredOutput("    ✅ Performance bonne (<5s)", 'green');
    }
    
    coloredOutput("", 'reset');
}

// Vérifier les fichiers générés
function checkGeneratedFiles() {
    $files = [
        '../temp/webcron_status.json' => 'Fichier de statut webcron',
        '../temp/webcron_last_alert.txt' => 'Dernière alerte (optionnel)',
        '../logs/security.log' => 'Logs de sécurité',
        '../logs/performance.log' => 'Logs de performance'
    ];
    
    foreach ($files as $file => $description) {
        if (file_exists($file)) {
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            coloredOutput("  ✅ $description: " . basename($file) . " ($size bytes, modifié: $modified)", 'green');
        } else {
            $optional = strpos($description, 'optionnel') !== false;
            if ($optional) {
                coloredOutput("  ⚪ $description: " . basename($file) . " (non créé - normal)", 'yellow');
            } else {
                coloredOutput("  ❌ $description: " . basename($file) . " (manquant)", 'red');
            }
        }
    }
    
    coloredOutput("", 'reset');
}

// Fonction pour afficher l'aide
function showHelp() {
    coloredOutput("=== AIDE TEST WEBCRON ===", 'blue');
    coloredOutput("Usage: php test-webcron.php [options]", 'reset');
    coloredOutput("", 'reset');
    coloredOutput("Options:", 'yellow');
    coloredOutput("  --url=URL        URL du webcron monitor", 'reset');
    coloredOutput("  --key=KEY        Clé secrète", 'reset');
    coloredOutput("  --timeout=SEC    Timeout en secondes", 'reset');
    coloredOutput("  --quiet          Mode silencieux", 'reset');
    coloredOutput("  --help           Afficher cette aide", 'reset');
    coloredOutput("", 'reset');
    coloredOutput("Exemple:", 'yellow');
    coloredOutput("  php test-webcron.php --url=https://remmailleuse.ch/api/webcron-monitor.php --key=ma_cle", 'reset');
    coloredOutput("", 'reset');
}

// Traiter les arguments de ligne de commande
function parseArguments() {
    global $TEST_CONFIG;
    
    $args = $_SERVER['argv'] ?? [];
    
    foreach ($args as $arg) {
        if (strpos($arg, '--url=') === 0) {
            $TEST_CONFIG['webcron_url'] = substr($arg, 6);
        } elseif (strpos($arg, '--key=') === 0) {
            $TEST_CONFIG['secret_key'] = substr($arg, 6);
        } elseif (strpos($arg, '--timeout=') === 0) {
            $TEST_CONFIG['timeout'] = (int)substr($arg, 10);
        } elseif ($arg === '--quiet') {
            $TEST_CONFIG['verbose'] = false;
        } elseif ($arg === '--help') {
            showHelp();
            exit(0);
        }
    }
}

// Point d'entrée principal
function main() {
    // Vérifier si nous sommes en CLI ou web
    if (php_sapi_name() !== 'cli') {
        echo "<pre style='font-family: monospace; background: #f0f0f0; padding: 20px;'>";
        echo "<h2>Test Webcron Monitoring - ReMmailleuse</h2>";
    }
    
    // Traiter les arguments
    parseArguments();
    
    // Vérifier les prérequis
    if (!function_exists('curl_init')) {
        coloredOutput("❌ ERREUR: cURL n'est pas installé", 'red');
        exit(1);
    }
    
    // Lancer le test
    testWebcron();
    
    if (php_sapi_name() !== 'cli') {
        echo "</pre>";
    }
}

// Exécuter le test
main();

?>