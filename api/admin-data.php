<?php
/**
 * API pour lire/écrire les données JSON depuis l'admin
 */
session_start();

// Vérifier l'authentification admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'load':
            $file = $_GET['file'] ?? '';
            $result = loadJsonFile($file);
            echo json_encode($result);
            break;
            
        case 'save':
            $file = $_POST['file'] ?? '';
            $data = json_decode($_POST['data'], true);
            echo json_encode(saveJsonFile($file, $data));
            break;
            
        case 'load_all':
            $contentData = loadJsonFile('content');
            $servicesData = loadJsonFile('services');
            $galleryData = loadJsonFile('gallery');
            $settingsData = loadJsonFile('settings');
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'content' => $contentData['data'],
                    'services' => $servicesData['data'],
                    'gallery' => $galleryData['data'],
                    'settings' => $settingsData['data']
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non valide']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function loadJsonFile($filename) {
    $filepath = "../data/{$filename}.json";
    
    if (!file_exists($filepath)) {
        throw new Exception("Fichier non trouvé: {$filename}.json");
    }
    
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur JSON dans {$filename}.json: " . json_last_error_msg());
    }
    
    return ['success' => true, 'data' => $data];
}

function saveJsonFile($filename, $data) {
    $filepath = "../data/{$filename}.json";
    
    // Créer une sauvegarde
    if (file_exists($filepath)) {
        $backupPath = "../data/backups/{$filename}_" . date('Y-m-d_H-i-s') . ".json";
        $backupDir = dirname($backupPath);
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        copy($filepath, $backupPath);
    }
    
    // Sauvegarder les nouvelles données
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filepath, $jsonData) === false) {
        throw new Exception("Impossible de sauvegarder {$filename}.json");
    }
    
    return ['success' => true, 'message' => "Données sauvegardées dans {$filename}.json"];
}
?>