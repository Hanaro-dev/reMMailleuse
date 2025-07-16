<?php
/**
 * API de gestion des sauvegardes
 * Création, restauration et gestion des sauvegardes JSON
 */

// Inclure les dépendances
require_once 'csrf.php';
require_once 'BackupManager.php';
require_once 'Logger.php';

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Gestion des requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Actions autorisées en GET sans authentification
$publicActions = ['auto', 'stats'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Validation CSRF pour les actions sensibles
if (!in_array($action, $publicActions)) {
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
    
    // Validation CSRF pour les actions POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        CSRFProtection::validateRequest();
    }
}

try {
    // Créer le gestionnaire de sauvegarde
    $backupManager = new BackupManager();
    
    // Router les actions
    switch ($action) {
        case 'create':
            handleCreateBackup($backupManager);
            break;
            
        case 'list':
            handleListBackups($backupManager);
            break;
            
        case 'restore':
            handleRestoreBackup($backupManager);
            break;
            
        case 'delete':
            handleDeleteBackup($backupManager);
            break;
            
        case 'auto':
            handleAutoBackup($backupManager);
            break;
            
        case 'stats':
            handleGetStats($backupManager);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Action non valide'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Crée une nouvelle sauvegarde
 */
function handleCreateBackup($backupManager) {
    $type = $_POST['type'] ?? 'manual';
    $result = $backupManager->createBackup($type);
    
    if ($result['success']) {
        // Log succès de sauvegarde
        logAPI('Sauvegarde créée avec succès', [
            'type' => $type,
            'backup_id' => $result['id'] ?? 'unknown',
            'size' => $result['size'] ?? 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'INFO');
        
        echo json_encode([
            'success' => true,
            'message' => 'Sauvegarde créée avec succès',
            'backup' => $result
        ]);
    } else {
        // Log erreur de sauvegarde
        logError('Erreur lors de la création de sauvegarde', [
            'type' => $type,
            'error' => $result['error'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'api');
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
}

/**
 * Liste toutes les sauvegardes
 */
function handleListBackups($backupManager) {
    $backups = $backupManager->listBackups();
    
    // Trier par date (plus récent en premier)
    usort($backups, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    echo json_encode([
        'success' => true,
        'backups' => $backups,
        'count' => count($backups)
    ]);
}

/**
 * Restaure une sauvegarde
 */
function handleRestoreBackup($backupManager) {
    $backupId = $_POST['backup_id'] ?? '';
    
    if (empty($backupId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de sauvegarde requis'
        ]);
        return;
    }
    
    $result = $backupManager->restoreBackup($backupId);
    
    if ($result['success']) {
        // Log succès de restauration
        logAPI('Sauvegarde restaurée avec succès', [
            'backup_id' => $backupId,
            'restored_files' => $result['restored_files'] ?? 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'INFO');
        
        echo json_encode([
            'success' => true,
            'message' => 'Sauvegarde restaurée avec succès',
            'restored' => $result
        ]);
    } else {
        // Log erreur de restauration
        logError('Erreur lors de la restauration de sauvegarde', [
            'backup_id' => $backupId,
            'error' => $result['error'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], 'api');
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
}

/**
 * Supprime une sauvegarde
 */
function handleDeleteBackup($backupManager) {
    $backupId = $_POST['backup_id'] ?? '';
    
    if (empty($backupId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de sauvegarde requis'
        ]);
        return;
    }
    
    // Utiliser la méthode privée via réflexion (pas idéal mais fonctionnel)
    $reflection = new ReflectionClass($backupManager);
    $deleteMethod = $reflection->getMethod('deleteBackup');
    $deleteMethod->setAccessible(true);
    
    try {
        $deleteMethod->invoke($backupManager, $backupId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sauvegarde supprimée avec succès'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Sauvegarde automatique si nécessaire
 */
function handleAutoBackup($backupManager) {
    $result = $backupManager->autoBackup();
    
    echo json_encode([
        'success' => true,
        'auto_backup' => $result
    ]);
}

/**
 * Obtient les statistiques des sauvegardes
 */
function handleGetStats($backupManager) {
    $stats = $backupManager->getBackupStats();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

// Déclencher une sauvegarde automatique aléatoire (5% de chance)
if (rand(1, 100) <= 5) {
    try {
        $backupManager = new BackupManager();
        $backupManager->autoBackup();
    } catch (Exception $e) {
        // Log erreur de sauvegarde automatique
        logError('Erreur sauvegarde automatique', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'api');
    }
}

?>