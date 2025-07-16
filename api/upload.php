<?php
/**
 * API d'upload d'images avec compression et optimisation
 * Endpoint sécurisé pour l'upload de photos
 */

// Inclure les dépendances
require_once 'csrf.php';
require_once 'ImageUploadManager.php';
require_once 'RateLimiter.php';
require_once 'Logger.php';

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

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

// Validation CSRF
CSRFProtection::validateRequest();

// Configuration
$CONFIG = [
    'upload' => [
        'max_files' => 5,
        'max_size' => 10 * 1024 * 1024, // 10MB
        'upload_dir' => __DIR__ . '/../uploads/images/',
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
    ]
];

try {
    // Vérifier le rate limiting global
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimiter = getRateLimiter();
    $rateLimitResult = $rateLimiter->isAllowed($clientIP, 'upload', [
        'endpoint' => 'upload',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    if (!$rateLimitResult['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Trop de tentatives d\'upload. Veuillez patienter avant de réessayer.',
            'retry_after' => $rateLimitResult['reset_time'] - time(),
            'reason' => $rateLimitResult['reason']
        ]);
        exit();
    }
    
    // Créer le gestionnaire d'upload
    $uploadManager = new ImageUploadManager($CONFIG);
    
    // Vérifier la présence de fichiers
    if (empty($_FILES['images'])) {
        throw new Exception('Aucun fichier à uploader');
    }
    
    // Traiter les uploads
    $uploadedImages = $uploadManager->handleMultipleUpload($_FILES['images'], $CONFIG['upload']['max_files']);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'message' => 'Images uploadées avec succès',
        'count' => count($uploadedImages),
        'images' => []
    ];
    
    // Formatter les données pour la réponse
    foreach ($uploadedImages as $image) {
        $response['images'][] = [
            'original_name' => $image['original_name'],
            'mime_type' => $image['mime_type'],
            'original_size' => $image['original_size'],
            'compressed_size' => $image['versions']['medium']['files']['jpeg']['size'],
            'compression_ratio' => round((1 - $image['versions']['medium']['files']['jpeg']['size'] / $image['original_size']) * 100, 1),
            'urls' => [
                'original' => $image['versions']['original']['files']['jpeg']['url'],
                'medium' => $image['versions']['medium']['files']['jpeg']['url'],
                'thumb' => $image['versions']['thumb']['files']['jpeg']['url'],
                'webp' => [
                    'original' => $image['versions']['original']['files']['webp']['url'] ?? null,
                    'medium' => $image['versions']['medium']['files']['webp']['url'] ?? null,
                    'thumb' => $image['versions']['thumb']['files']['webp']['url'] ?? null
                ]
            ],
            'dimensions' => [
                'original' => $image['versions']['original']['dimensions'],
                'medium' => $image['versions']['medium']['dimensions'],
                'thumb' => $image['versions']['thumb']['dimensions']
            ]
        ];
    }
    
    // Log de l'upload avec le système centralisé
    logAPI("Upload d'images réussi", [
        'files_count' => count($uploadedImages),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'total_size' => array_sum(array_column($uploadedImages, 'original_size')),
        'compression_ratio' => round(array_sum(array_map(function($img) {
            return (1 - $img['versions']['medium']['files']['jpeg']['size'] / $img['original_size']) * 100;
        }, $uploadedImages)) / count($uploadedImages), 1)
    ], 'INFO');
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log de l'erreur avec le système centralisé
    logError('Erreur upload d\'images', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ], 'upload');
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Nettoyage avec le nouveau système optimisé
if (rand(1, 25) === 1) { // 4% de chance à chaque requête
    require_once 'CleanupManager.php';
    $cleanupManager = new CleanupManager();
    $cleanupManager->runSmartCleanup();
}

/**
 * Nettoie les uploads anciens (plus de 30 jours)
 */
function cleanupOldUploads() {
    $uploadDir = __DIR__ . '/../uploads/images/';
    $maxAge = 30 * 24 * 60 * 60; // 30 jours
    
    if (!is_dir($uploadDir)) {
        return;
    }
    
    $files = glob($uploadDir . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            unlink($file);
        }
    }
}

?>