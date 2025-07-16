<?php
/**
 * ===== TEST EMAIL SYSTEM - SITE REMMAILLEUSE =====
 * Script de test pour vérifier l'envoi d'emails
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Inclure le gestionnaire d'emails
require_once 'EmailManager.php';
require_once 'Logger.php';

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit();
}

// Vérification basique d'accès (à adapter selon vos besoins)
$allowedIPs = ['127.0.0.1', '::1']; // Localhost seulement
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Accès refusé. Test disponible uniquement en local.'
    ]);
    exit();
}

try {
    // Initialiser le gestionnaire d'emails
    $emailManager = new EmailManager();
    
    // Données de test
    $testData = [
        'firstname' => 'Test',
        'lastname' => 'Utilisateur',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'message' => 'Ceci est un test du système d\'email de notification admin.'
    ];
    
    $testUploads = [
        [
            'original_name' => 'test-image.jpg',
            'size' => 150000,
            'type' => 'image/jpeg'
        ]
    ];
    
    // Tests différents types d'emails
    $results = [];
    
    // Test 1: Email de test simple
    $results['test_email'] = $emailManager->testEmail();
    
    // Test 2: Notification de nouveau contact
    $results['contact_notification'] = $emailManager->sendAdminNotification('new_contact', $testData, $testUploads);
    
    // Test 3: Alerte de sécurité
    $results['security_alert'] = $emailManager->sendSecurityAlert('test_alert', 'Test d\'alerte de sécurité depuis le script de test');
    
    // Test 4: Erreur d'upload
    $results['upload_error'] = $emailManager->sendUploadError('Test d\'erreur d\'upload depuis le script de test');
    
    // Résultats
    $allSuccess = !in_array(false, $results);
    
    // Log des résultats de test
    logAPI('Test d\'emails exécuté', [
        'success' => $allSuccess,
        'results' => $results,
        'ip' => $clientIP
    ], $allSuccess ? 'INFO' : 'WARNING');
    
    echo json_encode([
        'success' => $allSuccess,
        'message' => $allSuccess ? 'Tous les tests d\'email ont réussi' : 'Certains tests ont échoué',
        'results' => $results,
        'details' => [
            'test_email' => $results['test_email'] ? 'Email de test envoyé' : 'Échec email de test',
            'contact_notification' => $results['contact_notification'] ? 'Notification contact envoyée' : 'Échec notification contact',
            'security_alert' => $results['security_alert'] ? 'Alerte sécurité envoyée' : 'Échec alerte sécurité',
            'upload_error' => $results['upload_error'] ? 'Notification erreur envoyée' : 'Échec notification erreur'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log erreur de test
    logError('Erreur lors du test d\'emails', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'ip' => $clientIP
    ], 'email');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du test: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}