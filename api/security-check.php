<?php
/**
 * Security Check API for ReMmailleuse
 * 
 * Endpoint pour vérifier la configuration de sécurité
 * Accessible uniquement depuis l'interface admin
 */

// Inclure les dépendances
require_once 'SecurityHeaders.php';
require_once 'Logger.php';

// Vérifier l'authentification admin
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentification requise'
    ]);
    exit();
}

// Initialiser les en-têtes de sécurité
$logger = new Logger();
initSecurityHeaders('api', $logger);

// Traitement selon l'action
$action = $_GET['action'] ?? 'check';

try {
    $security = new SecurityHeaders($logger);
    
    switch ($action) {
        case 'check':
            $validation = $security->validateSecurityConfig();
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            break;
            
        case 'report':
            $report = $security->generateSecurityReport();
            echo json_encode([
                'success' => true,
                'report' => $report
            ]);
            break;
            
        case 'headers':
            // Obtenir les en-têtes actuels
            $headers = [];
            if (function_exists('headers_list')) {
                foreach (headers_list() as $header) {
                    $headers[] = $header;
                }
            }
            
            echo json_encode([
                'success' => true,
                'headers' => $headers
            ]);
            break;
            
        case 'csp-test':
            // Tester la CSP
            $cspTest = [
                'current_policy' => $_SERVER['HTTP_CONTENT_SECURITY_POLICY'] ?? 'Non défini',
                'violations' => [], // Ici on pourrait logger les violations CSP
                'recommendations' => [
                    'Activer le rapport des violations CSP',
                    'Utiliser des nonces pour les scripts inline',
                    'Minimiser l\'utilisation de \'unsafe-inline\'',
                    'Considérer l\'utilisation de sous-ressources intégrées'
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'csp_test' => $cspTest
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Action non reconnue'
            ]);
    }
    
} catch (Exception $e) {
    $logger->error('Erreur lors du check de sécurité: ' . $e->getMessage(), [
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'security');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>