<?php
/**
 * API contact simplifiée pour test
 */

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Gestion des requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit();
}

try {
    // Récupération des données
    $rawData = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $rawData = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Données JSON invalides');
        }
    } else {
        $rawData = $_POST;
    }
    
    // Validation
    $requiredFields = ['firstname', 'lastname', 'email', 'message'];
    foreach ($requiredFields as $field) {
        if (empty($rawData[$field])) {
            throw new Exception("Champ obligatoire manquant: $field");
        }
    }
    
    // Validation email
    if (!filter_var($rawData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }
    
    // Simulation d'envoi d'email
    $emailData = [
        'to' => 'contact@remmailleuse.ch',
        'subject' => '[Site Web] Nouvelle demande de devis',
        'message' => "Nom: {$rawData['firstname']} {$rawData['lastname']}\n" .
                    "Email: {$rawData['email']}\n" .
                    "Téléphone: " . ($rawData['phone'] ?? 'Non renseigné') . "\n\n" .
                    "Message:\n{$rawData['message']}\n\n" .
                    "Date: " . date('d/m/Y à H:i:s') . "\n" .
                    "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue'),
        'headers' => "From: site@remmailleuse.ch\r\n" .
                    "Reply-To: {$rawData['email']}\r\n" .
                    "Content-Type: text/plain; charset=UTF-8\r\n"
    ];
    
    // Pour le test, on log au lieu d'envoyer
    $logFile = dirname(__DIR__) . '/logs/contact.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = "[" . date('Y-m-d H:i:s') . "] CONTACT: " . json_encode($emailData) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Réponse succès
    echo json_encode([
        'success' => true,
        'message' => 'Votre demande a été envoyée avec succès !',
        'debug' => [
            'data_received' => $rawData,
            'email_prepared' => $emailData,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'raw_input' => file_get_contents('php://input'),
            'post_data' => $_POST,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ]
    ]);
}
?>