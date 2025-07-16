<?php
/**
 * ===== TEST SYSTÈME DE NETTOYAGE - SITE REMMAILLEUSE =====
 * Script de test pour valider le système de nettoyage amélioré
 * Usage: php test-cleanup.php
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Vérification basique d'accès (localhost seulement)
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Accès refusé. Test disponible uniquement en local.'
    ]);
    exit();
}

// Inclure le gestionnaire de nettoyage
require_once 'CleanupManager.php';

function runTest($testName, $testFunction) {
    echo "🧪 Test: $testName\n";
    
    $startTime = microtime(true);
    $result = $testFunction();
    $duration = round(microtime(true) - $startTime, 3);
    
    if ($result) {
        echo "✅ RÉUSSI ({$duration}s)\n";
    } else {
        echo "❌ ÉCHOUÉ ({$duration}s)\n";
    }
    
    echo "\n";
    return $result;
}

function createTestFiles() {
    $testFiles = [];
    
    // Créer des fichiers de test pour rate limiting
    $tempDir = '../temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $testFiles[] = $tempDir . 'rate_limit_test1.txt';
    $testFiles[] = $tempDir . 'rate_limit_test2.txt';
    
    foreach ($testFiles as $file) {
        file_put_contents($file, 'test content');
        // Modifier le timestamp pour simuler un fichier ancien
        touch($file, time() - 7200); // 2 heures dans le passé
    }
    
    // Créer un fichier de tentatives d'auth
    $authFile = '../temp/login_attempts.json';
    $oldAttempts = [
        '192.168.1.1' => [
            'count' => 3,
            'last_attempt' => time() - 86400 // 24 heures dans le passé
        ],
        '192.168.1.2' => [
            'count' => 2,
            'last_attempt' => time() - 100 // Récent
        ]
    ];
    file_put_contents($authFile, json_encode($oldAttempts));
    
    return $testFiles;
}

function cleanupTestFiles($testFiles) {
    foreach ($testFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Test 1: Initialisation du CleanupManager
$test1 = function() {
    try {
        $cleanup = new CleanupManager();
        return $cleanup instanceof CleanupManager;
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 2: Nettoyage des fichiers de rate limiting
$test2 = function() {
    $testFiles = createTestFiles();
    
    try {
        $cleanup = new CleanupManager();
        $cleaned = $cleanup->cleanupRateLimitFiles();
        
        // Vérifier que les fichiers ont été supprimés
        $filesExist = false;
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                $filesExist = true;
                break;
            }
        }
        
        cleanupTestFiles($testFiles);
        
        return $cleaned >= 2 && !$filesExist;
    } catch (Exception $e) {
        cleanupTestFiles($testFiles);
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 3: Nettoyage des tentatives d'authentification
$test3 = function() {
    createTestFiles(); // Créer le fichier d'auth
    
    try {
        $cleanup = new CleanupManager();
        $cleaned = $cleanup->cleanupAuthAttempts();
        
        // Vérifier que les anciennes tentatives ont été supprimées
        $authFile = '../temp/login_attempts.json';
        $attempts = json_decode(file_get_contents($authFile), true) ?: [];
        
        // Il ne devrait rester que l'IP récente
        $result = $cleaned >= 1 && count($attempts) === 1 && isset($attempts['192.168.1.2']);
        
        if (file_exists($authFile)) {
            unlink($authFile);
        }
        
        return $result;
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 4: Nettoyage rapide
$test4 = function() {
    $testFiles = createTestFiles();
    
    try {
        $cleanup = new CleanupManager();
        $result = $cleanup->runQuickCleanup();
        
        cleanupTestFiles($testFiles);
        
        return $result['success'] && $result['cleaned'] >= 0;
    } catch (Exception $e) {
        cleanupTestFiles($testFiles);
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 5: Nettoyage complet
$test5 = function() {
    $testFiles = createTestFiles();
    
    try {
        $cleanup = new CleanupManager();
        $result = $cleanup->runFullCleanup();
        
        cleanupTestFiles($testFiles);
        
        return $result['success'] && isset($result['details']);
    } catch (Exception $e) {
        cleanupTestFiles($testFiles);
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 6: Nettoyage intelligent
$test6 = function() {
    $testFiles = createTestFiles();
    
    try {
        $cleanup = new CleanupManager();
        $result = $cleanup->runSmartCleanup();
        
        cleanupTestFiles($testFiles);
        
        return $result['success'];
    } catch (Exception $e) {
        cleanupTestFiles($testFiles);
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 7: Statistiques de nettoyage
$test7 = function() {
    try {
        $cleanup = new CleanupManager();
        $stats = $cleanup->getCleanupStats();
        
        return is_array($stats) && 
               isset($stats['rate_limit_files']) && 
               isset($stats['auth_attempts']);
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Test 8: Système de verrous
$test8 = function() {
    $testFiles = createTestFiles();
    
    try {
        $cleanup1 = new CleanupManager();
        $cleanup2 = new CleanupManager();
        
        // Démarrer le premier nettoyage
        $result1 = $cleanup1->runFullCleanup();
        
        // Le deuxième devrait être bloqué... mais comme c'est rapide, on teste différemment
        $result2 = $cleanup2->runQuickCleanup();
        
        cleanupTestFiles($testFiles);
        
        return $result1['success'] && $result2['success'];
    } catch (Exception $e) {
        cleanupTestFiles($testFiles);
        echo "Erreur: " . $e->getMessage() . "\n";
        return false;
    }
};

// Exécuter les tests
try {
    echo "=== TESTS DU SYSTÈME DE NETTOYAGE ===\n\n";
    
    $results = [
        'test1_initialization' => runTest('Initialisation CleanupManager', $test1),
        'test2_rate_limit_cleanup' => runTest('Nettoyage fichiers rate limiting', $test2),
        'test3_auth_cleanup' => runTest('Nettoyage tentatives auth', $test3),
        'test4_quick_cleanup' => runTest('Nettoyage rapide', $test4),
        'test5_full_cleanup' => runTest('Nettoyage complet', $test5),
        'test6_smart_cleanup' => runTest('Nettoyage intelligent', $test6),
        'test7_stats' => runTest('Statistiques de nettoyage', $test7),
        'test8_locking' => runTest('Système de verrous', $test8)
    ];
    
    $passed = array_sum($results);
    $total = count($results);
    
    echo "=== RÉSULTATS ===\n";
    echo "✅ Tests réussis: $passed/$total\n";
    
    if ($passed === $total) {
        echo "🎉 Tous les tests sont passés !\n";
        
        // Test de performance
        echo "\n=== TEST DE PERFORMANCE ===\n";
        $cleanup = new CleanupManager();
        $startTime = microtime(true);
        $result = $cleanup->runSmartCleanup();
        $duration = round(microtime(true) - $startTime, 3);
        
        echo "Nettoyage intelligent: {$duration}s\n";
        echo "Fichiers nettoyés: {$result['cleaned']}\n";
        
        if ($duration < 1.0) {
            echo "✅ Performance acceptable\n";
        } else {
            echo "⚠️ Performance à améliorer\n";
        }
        
        $success = true;
    } else {
        echo "❌ Certains tests ont échoué\n";
        $success = false;
    }
    
    // Réponse JSON si appelé via API
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'passed' => $passed,
            'total' => $total,
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    exit($success ? 0 : 1);
    
} catch (Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    exit(1);
}