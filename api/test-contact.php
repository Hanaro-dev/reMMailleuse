<?php
/**
 * Test simple pour l'API contact sans CSRF
 */

// Headers de sécurité basiques
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit();
}

// Récupération des données
$rawData = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $json = file_get_contents('php://input');
    $rawData = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Données JSON invalides'
        ]);
        exit();
    }
} else {
    $rawData = $_POST;
}

// Validation basique
$requiredFields = ['firstname', 'lastname', 'email', 'message'];
foreach ($requiredFields as $field) {
    if (empty($rawData[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Champ obligatoire manquant: $field"
        ]);
        exit();
    }
}

// Validation email
if (!filter_var($rawData['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Email invalide'
    ]);
    exit();
}

// Simuler l'envoi d'email
$to = 'test@example.com';
$subject = 'Test - Demande de contact';
$message = "Nom: {$rawData['firstname']} {$rawData['lastname']}\n";
$message .= "Email: {$rawData['email']}\n";
$message .= "Message: {$rawData['message']}\n";

$headers = "From: noreply@test.com\r\n";
$headers .= "Reply-To: {$rawData['email']}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Log au lieu d'envoyer vraiment l'email
error_log("TEST EMAIL: " . $message);

// Réponse succès
echo json_encode([
    'success' => true,
    'message' => 'Email de test enregistré avec succès',
    'debug' => [
        'to' => $to,
        'subject' => $subject,
        'data' => $rawData
    ]
]);
?>