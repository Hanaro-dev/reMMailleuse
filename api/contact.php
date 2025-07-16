<?php
/**
 * ===== API CONTACT - SITE REMMAILLEUSE =====
 * Gestionnaire de formulaire de contact avec upload d'images
 * Compatible Infomaniak/PHP 8+
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 janvier 2025
 */

// Inclure le système de protection CSRF
require_once 'csrf.php';
require_once 'ImageUploadManager.php';
require_once 'EmailManager.php';
require_once 'CacheManager.php';
require_once 'RateLimiter.php';
require_once 'Logger.php';
require_once 'SecurityHeaders.php';
require_once 'rate-limit-helpers.php';

// Initialiser les en-têtes de sécurité
$logger = new Logger();
initSecurityHeaders('api', $logger);

// Headers de sécurité et CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://remmailleuse.ch');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

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
    'email' => [
        'to' => 'contact@remmailleuse.ch',
        'from' => 'noreply@remmailleuse.ch',
        'admin' => 'admin@remmailleuse.ch'
    ],
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'max_files' => 5,
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'upload_dir' => __DIR__ . '/../uploads/contact/'
    ],
    'security' => [
        'max_attempts' => 5,
        'cooldown' => 300, // 5 minutes
        'honeypot_field' => 'website' // Champ piège anti-spam
    ]
];

// Fonction de logging sécurisé
function logSecurity($message, $level = 'INFO') {
    $logFile = '../logs/contact_security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = "[$timestamp] [$level] IP:$ip - $message - UA:$userAgent\n";
    
    // Créer le dossier logs s'il n'existe pas
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Fonction de validation d'email renforcée
function isValidEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Vérifications additionnelles
    $domain = substr(strrchr($email, "@"), 1);
    
    // Blacklist de domaines temporaires/spam
    $blockedDomains = [
        '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
        'tempmail.com', 'yopmail.com', 'throw-away-email.com'
    ];
    
    if (in_array(strtolower($domain), $blockedDomains)) {
        return false;
    }
    
    // Vérifier que le domaine existe (optionnel, peut ralentir)
    // return checkdnsrr($domain, 'MX');
    
    return true;
}

// Fonction anti-spam et rate limiting
function checkRateLimit($ip) {
    global $CONFIG;
    
    $attemptFile = '../temp/rate_limit_' . md5($ip) . '.txt';
    $now = time();
    
    if (file_exists($attemptFile)) {
        $attempts = json_decode(file_get_contents($attemptFile), true);
        
        // Nettoyer les tentatives anciennes
        $attempts = array_filter($attempts, function($timestamp) use ($now, $CONFIG) {
            return ($now - $timestamp) < $CONFIG['security']['cooldown'];
        });
        
        if (count($attempts) >= $CONFIG['security']['max_attempts']) {
            return false;
        }
        
        $attempts[] = $now;
    } else {
        $attempts = [$now];
    }
    
    // Créer le dossier temp s'il n'existe pas
    $tempDir = dirname($attemptFile);
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    file_put_contents($attemptFile, json_encode($attempts));
    return true;
}

// Fonction de nettoyage et validation des données
function sanitizeInput($data) {
    $cleaned = [];
    
    $cleaned['firstname'] = trim(filter_var($data['firstname'] ?? '', FILTER_SANITIZE_STRING));
    $cleaned['lastname'] = trim(filter_var($data['lastname'] ?? '', FILTER_SANITIZE_STRING));
    $cleaned['email'] = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $cleaned['phone'] = trim(filter_var($data['phone'] ?? '', FILTER_SANITIZE_STRING));
    $cleaned['message'] = trim(filter_var($data['message'] ?? '', FILTER_SANITIZE_STRING));
    
    // Champ honeypot (doit être vide)
    $cleaned['honeypot'] = $data['website'] ?? '';
    
    return $cleaned;
}

// Fonction de validation
function validateData($data) {
    $errors = [];
    
    // Vérification honeypot (anti-spam)
    if (!empty($data['honeypot'])) {
        logSecurity('Tentative de spam détectée - honeypot rempli', 'WARNING');
        $errors[] = 'Requête invalide';
    }
    
    // Validations obligatoires
    if (empty($data['firstname']) || strlen($data['firstname']) < 2) {
        $errors[] = 'Le prénom est obligatoire (minimum 2 caractères)';
    }
    
    if (empty($data['lastname']) || strlen($data['lastname']) < 2) {
        $errors[] = 'Le nom est obligatoire (minimum 2 caractères)';
    }
    
    if (empty($data['email']) || !isValidEmail($data['email'])) {
        $errors[] = 'Une adresse email valide est obligatoire';
    }
    
    if (empty($data['message']) || strlen($data['message']) < 10) {
        $errors[] = 'Le message est obligatoire (minimum 10 caractères)';
    }
    
    // Validation téléphone (optionnel mais si rempli)
    if (!empty($data['phone'])) {
        $phonePattern = '/^[\+]?[0-9\s\-\(\)\.]{8,20}$/';
        if (!preg_match($phonePattern, $data['phone'])) {
            $errors[] = 'Format de téléphone invalide';
        }
    }
    
    // Vérification longueurs maximales
    if (strlen($data['firstname']) > 50) $errors[] = 'Prénom trop long (max 50 caractères)';
    if (strlen($data['lastname']) > 50) $errors[] = 'Nom trop long (max 50 caractères)';
    if (strlen($data['message']) > 2000) $errors[] = 'Message trop long (max 2000 caractères)';
    
    return $errors;
}

// Fonction de gestion des fichiers uploadés avec compression
function handleFileUploads() {
    global $CONFIG;
    
    $uploadedFiles = [];
    
    if (empty($_FILES['photos']['name'][0])) {
        return $uploadedFiles; // Pas de fichiers
    }
    
    try {
        // Créer le gestionnaire d'upload
        $uploadManager = new ImageUploadManager($CONFIG);
        
        // Traiter les uploads avec compression
        $processedImages = $uploadManager->handleMultipleUpload($_FILES['photos'], $CONFIG['upload']['max_files']);
        
        // Formatter pour compatibilité avec l'ancien système
        foreach ($processedImages as $image) {
            $uploadedFiles[] = [
                'original_name' => $image['original_name'],
                'saved_name' => basename($image['versions']['medium']['files']['jpeg']['path']),
                'path' => $image['versions']['medium']['files']['jpeg']['path'],
                'size' => $image['versions']['medium']['files']['jpeg']['size'],
                'original_size' => $image['original_size'],
                'compression_ratio' => round((1 - $image['versions']['medium']['files']['jpeg']['size'] / $image['original_size']) * 100, 1),
                'versions' => [
                    'original' => $image['versions']['original']['files']['jpeg']['url'],
                    'medium' => $image['versions']['medium']['files']['jpeg']['url'],
                    'thumb' => $image['versions']['thumb']['files']['jpeg']['url']
                ],
                'webp_versions' => [
                    'original' => $image['versions']['original']['files']['webp']['url'] ?? null,
                    'medium' => $image['versions']['medium']['files']['webp']['url'] ?? null,
                    'thumb' => $image['versions']['thumb']['files']['webp']['url'] ?? null
                ]
            ];
        }
        
        return $uploadedFiles;
        
    } catch (Exception $e) {
        throw new Exception("Erreur lors du traitement des images: " . $e->getMessage());
    }
}

// Fonction d'envoi d'email
function sendEmails($data, $uploadedFiles) {
    global $CONFIG;
    
    $to = $CONFIG['email']['to'];
    $from = $CONFIG['email']['from'];
    $subject = 'Nouvelle demande de devis - Site Remmailleuse';
    
    // Construire le message
    $message = "=== NOUVELLE DEMANDE DE DEVIS ===\n\n";
    $message .= "Prénom: {$data['firstname']}\n";
    $message .= "Nom: {$data['lastname']}\n";
    $message .= "Email: {$data['email']}\n";
    $message .= "Téléphone: " . ($data['phone'] ?: 'Non renseigné') . "\n\n";
    $message .= "Message:\n" . wordwrap($data['message'], 70) . "\n\n";
    
    if (!empty($uploadedFiles)) {
        $message .= "=== PHOTOS JOINTES ===\n";
        foreach ($uploadedFiles as $file) {
            $message .= "- {$file['original_name']} (" . round($file['size']/1024) . " Ko)\n";
        }
        $message .= "\nLes photos sont disponibles dans le dossier uploads/contact/\n\n";
    }
    
    $message .= "=== INFORMATIONS TECHNIQUES ===\n";
    $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
    $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
    $message .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu') . "\n";
    
    // Headers d'email
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: {$data['email']}\r\n";
    $headers .= "X-Mailer: Site Remmailleuse Contact Form\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Envoi email principal
    $emailSent = mail($to, $subject, $message, $headers);
    
    // Email de confirmation au client
    if ($emailSent) {
        $confirmSubject = 'Confirmation de votre demande - Remmailleuse';
        $confirmMessage = "Bonjour {$data['firstname']},\n\n";
        $confirmMessage .= "Merci pour votre demande de devis pour mes services de remaillage.\n\n";
        $confirmMessage .= "Votre message a bien été reçu et je vous répondrai dans les plus brefs délais (généralement sous 24h).\n\n";
        $confirmMessage .= "À bientôt,\n";
        $confirmMessage .= "Mme Monod\n";
        $confirmMessage .= "Artisane Remmailleuse\n\n";
        $confirmMessage .= "---\n";
        $confirmMessage .= "Rappel de votre demande:\n";
        $confirmMessage .= wordwrap($data['message'], 70) . "\n";
        
        $confirmHeaders = "From: $from\r\n";
        $confirmHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($data['email'], $confirmSubject, $confirmMessage, $confirmHeaders);
    }
    
    return $emailSent;
}

// ===== TRAITEMENT PRINCIPAL =====

try {
    // Initialiser le cache
    $cacheManager = new CacheManager([
        'cache_dir' => dirname(__DIR__) . '/cache/',
        'default_ttl' => 300, // 5 minutes pour les réponses API
        'compression' => true
    ]);
    
    // Vérifier le cache des réponses d'erreur pour éviter les attaques répétées
    $cacheKey = "rate_limit_response_" . md5($_SERVER['REMOTE_ADDR']);
    $cachedResponse = $cacheManager->getCachedHttpResponse($cacheKey);
    
    if ($cachedResponse !== null) {
        echo $cachedResponse;
        exit();
    }
    
    // Vérification rate limiting global
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $rateLimiter = getRateLimiter();
    $rateLimitResult = $rateLimiter->isAllowed($clientIP, 'contact', [
        'endpoint' => 'contact',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    if (!$rateLimitResult['allowed']) {
        logSecurity('Rate limit global atteint - ' . $rateLimitResult['reason'], 'WARNING');
        
        $errorResponse = json_encode([
            'success' => false,
            'error' => 'Trop de tentatives. Veuillez patienter avant de réessayer.',
            'retry_after' => $rateLimitResult['reset_time'] - time(),
            'reason' => $rateLimitResult['reason']
        ]);
        
        // Cacher la réponse d'erreur pour éviter les attaques répétées
        $cacheManager->cacheHttpResponse($cacheKey, $errorResponse, [], 300);
        
        http_response_code(429);
        echo $errorResponse;
        exit();
    }
    
    // Vérification rate limiting legacy
    if (!checkRateLimit($clientIP)) {
        logSecurity('Rate limit legacy atteint', 'WARNING');
        
        $errorResponse = json_encode([
            'success' => false,
            'error' => 'Trop de tentatives. Veuillez patienter 5 minutes.'
        ]);
        
        // Cacher la réponse d'erreur pour éviter les attaques répétées
        $cacheManager->cacheHttpResponse($cacheKey, $errorResponse, [], 300);
        
        http_response_code(429);
        echo $errorResponse;
        exit();
    }
    
    // Récupération et nettoyage des données
    $rawData = [];
    
    // Support des données JSON et form-data
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
    
    // Nettoyage et validation
    $cleanData = sanitizeInput($rawData);
    $validationErrors = validateData($cleanData);
    
    if (!empty($validationErrors)) {
        logSecurity('Données invalides: ' . implode(', ', $validationErrors), 'WARNING');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Données invalides',
            'details' => $validationErrors
        ]);
        exit();
    }
    
    // Gestion des fichiers uploadés
    $uploadedFiles = [];
    try {
        $uploadedFiles = handleFileUploads();
    } catch (Exception $e) {
        logSecurity('Erreur upload: ' . $e->getMessage(), 'ERROR');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur upload: ' . $e->getMessage()
        ]);
        exit();
    }
    
    // Envoi des emails avec le nouveau système
    $emailManager = new EmailManager();
    $emailSent = $emailManager->sendContactEmail($cleanData, $uploadedFiles);
    
    if ($emailSent) {
        // Log succès
        logAPI("Demande de contact envoyée avec succès", [
            'email' => $cleanData['email'],
            'name' => $cleanData['firstname'] . ' ' . $cleanData['lastname'],
            'files_count' => count($uploadedFiles),
            'ip' => $clientIP
        ], 'INFO');
        
        // Réponse succès
        echo json_encode([
            'success' => true,
            'message' => 'Votre demande a été envoyée avec succès !',
            'files_uploaded' => count($uploadedFiles)
        ]);
    } else {
        throw new Exception('Erreur lors de l\'envoi de l\'email');
    }
    
} catch (Exception $e) {
    // Log erreur
    logError('Erreur API contact', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'ip' => $clientIP,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ], 'api');
    
    // Réponse erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
        // Message de debug retiré pour la production
    ]);
}

// Nettoyage avec le nouveau système optimisé
if (rand(1, 30) === 1) { // 3.3% de chance à chaque requête
    require_once 'CleanupManager.php';
    $cleanupManager = new CleanupManager();
    $cleanupManager->runSmartCleanup();
}

?>