<?php
/**
 * ===== ADMINISTRATION RATE LIMITING - SITE REMMAILLEUSE =====
 * Interface d'administration pour le système de rate limiting global
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

session_start();

// Vérification authentification admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

require_once 'csrf.php';
require_once 'RateLimiter.php';
require_once 'Logger.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Validation CSRF pour les actions de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::validateRequest();
}

$rateLimiter = getRateLimiter();
$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

try {
    switch ($action) {
        case 'stats':
            handleGetStats($rateLimiter);
            break;
            
        case 'detailed_report':
            handleDetailedReport($rateLimiter);
            break;
            
        case 'check_ip':
            handleCheckIP($rateLimiter);
            break;
            
        case 'unblock_ip':
            handleUnblockIP($rateLimiter);
            break;
            
        case 'add_whitelist':
            handleAddWhitelist($rateLimiter);
            break;
            
        case 'remove_whitelist':
            handleRemoveWhitelist($rateLimiter);
            break;
            
        case 'add_blacklist':
            handleAddBlacklist($rateLimiter);
            break;
            
        case 'remove_blacklist':
            handleRemoveBlacklist($rateLimiter);
            break;
            
        case 'cleanup':
            handleCleanup($rateLimiter);
            break;
            
        case 'reset_all':
            handleResetAll($rateLimiter);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    
} catch (Exception $e) {
    // Log erreur rate limiting admin
    logError('Erreur rate limiting admin', [
        'action' => $action,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user' => $_SESSION['admin_username'] ?? 'unknown'
    ], 'security');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Obtenir les statistiques globales
 */
function handleGetStats($rateLimiter) {
    $stats = $rateLimiter->getStats();
    
    // Log consultation stats
    logSecurity('Consultation stats rate limiting', [
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'total_requests' => $stats['global']['total_requests'] ?? 0
    ], 'INFO');
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Obtenir le rapport détaillé
 */
function handleDetailedReport($rateLimiter) {
    $report = $rateLimiter->getDetailedReport();
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Vérifier une IP spécifique
 */
function handleCheckIP($rateLimiter) {
    $ip = $_GET['ip'] ?? '';
    $rule = $_GET['rule'] ?? 'general';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format IP invalide']);
        return;
    }
    
    $stats = $rateLimiter->getStats($ip, $rule);
    
    // Log vérification IP
    logSecurity('Vérification IP rate limiting', [
        'checked_ip' => $ip,
        'rule' => $rule,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'is_blocked' => $stats['blocked_until'] ? 'true' : 'false'
    ], 'INFO');
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Débloquer une IP
 */
function handleUnblockIP($rateLimiter) {
    $ip = $_POST['ip'] ?? '';
    $rule = $_POST['rule'] ?? 'general';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format IP invalide']);
        return;
    }
    
    $rateLimiter->unblock($ip, $rule);
    
    // Log déblocage IP
    logSecurity('Déblocage IP rate limiting', [
        'unblocked_ip' => $ip,
        'rule' => $rule,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'WARNING');
    
    echo json_encode([
        'success' => true,
        'message' => "IP $ip débloquée pour la règle $rule"
    ]);
}

/**
 * Ajouter une IP à la whitelist
 */
function handleAddWhitelist($rateLimiter) {
    $ip = $_POST['ip'] ?? '';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format IP invalide']);
        return;
    }
    
    $rateLimiter->addToWhitelist($ip);
    
    // Log ajout whitelist
    logSecurity('Ajout IP à la whitelist', [
        'whitelisted_ip' => $ip,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => "IP $ip ajoutée à la whitelist"
    ]);
}

/**
 * Retirer une IP de la whitelist
 */
function handleRemoveWhitelist($rateLimiter) {
    $ip = $_POST['ip'] ?? '';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    $rateLimiter->removeFromWhitelist($ip);
    
    // Log suppression whitelist
    logSecurity('Suppression IP de la whitelist', [
        'removed_ip' => $ip,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'WARNING');
    
    echo json_encode([
        'success' => true,
        'message' => "IP $ip retirée de la whitelist"
    ]);
}

/**
 * Ajouter une IP à la blacklist
 */
function handleAddBlacklist($rateLimiter) {
    $ip = $_POST['ip'] ?? '';
    $reason = $_POST['reason'] ?? 'admin_manual';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format IP invalide']);
        return;
    }
    
    $rateLimiter->addToBlacklist($ip, $reason);
    
    // Log ajout blacklist
    logSecurity('Ajout IP à la blacklist', [
        'blacklisted_ip' => $ip,
        'reason' => $reason,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'WARNING');
    
    echo json_encode([
        'success' => true,
        'message' => "IP $ip ajoutée à la blacklist"
    ]);
}

/**
 * Retirer une IP de la blacklist
 */
function handleRemoveBlacklist($rateLimiter) {
    $ip = $_POST['ip'] ?? '';
    
    if (empty($ip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP manquante']);
        return;
    }
    
    $rateLimiter->removeFromBlacklist($ip);
    
    // Log suppression blacklist
    logSecurity('Suppression IP de la blacklist', [
        'removed_ip' => $ip,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => "IP $ip retirée de la blacklist"
    ]);
}

/**
 * Nettoyer les anciens fichiers
 */
function handleCleanup($rateLimiter) {
    $cleaned = $rateLimiter->cleanup();
    
    // Log nettoyage
    logSecurity('Nettoyage rate limiting', [
        'cleaned_files' => $cleaned,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => "$cleaned fichiers nettoyés",
        'cleaned_files' => $cleaned
    ]);
}

/**
 * Réinitialiser toutes les limites
 */
function handleResetAll($rateLimiter) {
    // Confirmation supplémentaire pour cette action critique
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm !== 'RESET_ALL_LIMITS') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Confirmation manquante. Utilisez "RESET_ALL_LIMITS" pour confirmer.'
        ]);
        return;
    }
    
    $reset = $rateLimiter->resetAll();
    
    // Log réinitialisation (action critique)
    logSecurity('Réinitialisation complète rate limiting', [
        'reset_files' => $reset,
        'user' => $_SESSION['admin_username'] ?? 'unknown',
        'admin_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'confirm' => $confirm
    ], 'WARNING');
    
    echo json_encode([
        'success' => true,
        'message' => "Toutes les limites ont été réinitialisées",
        'reset_files' => $reset
    ]);
}

?>