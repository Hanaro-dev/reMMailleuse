<?php
/**
 * Bootstrap pour les tests PHPUnit
 * Configuration de l'environnement de test
 */

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de l'environnement de test
$_ENV['APP_ENV'] = 'testing';
$_ENV['TEST_MODE'] = true;

// Configuration des répertoires
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));
define('API_ROOT', PROJECT_ROOT . '/api');
define('DATA_ROOT', PROJECT_ROOT . '/data');
define('UPLOADS_ROOT', PROJECT_ROOT . '/uploads');
define('LOGS_ROOT', PROJECT_ROOT . '/logs');
define('TEMP_ROOT', PROJECT_ROOT . '/temp');

// Configuration des chemins de test
define('TEST_FIXTURES', TEST_ROOT . '/fixtures');
define('TEST_TEMP', TEST_ROOT . '/temp');
define('TEST_UPLOADS', TEST_ROOT . '/uploads');

// Créer les répertoires de test nécessaires
$testDirs = [
    TEST_TEMP,
    TEST_UPLOADS,
    TEST_UPLOADS . '/contact',
    TEST_UPLOADS . '/images',
    LOGS_ROOT
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Fonction d'aide pour nettoyer les répertoires de test
function cleanTestDirectories() {
    $dirs = [TEST_TEMP, TEST_UPLOADS];
    
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}

// Fonction d'aide pour créer un fichier de test
function createTestFile($path, $content = 'test content') {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
    return $path;
}

// Fonction d'aide pour créer une image de test
function createTestImage($path, $width = 100, $height = 100) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Créer une image simple
    $image = imagecreate($width, $height);
    $background = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    
    imagestring($image, 5, 10, 10, 'TEST', $textColor);
    
    // Déterminer le format par l'extension
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($image, $path, 90);
            break;
        case 'png':
            imagepng($image, $path);
            break;
        case 'gif':
            imagegif($image, $path);
            break;
        default:
            imagepng($image, $path);
    }
    
    imagedestroy($image);
    return $path;
}

// Fonction d'aide pour créer des données de test
function getTestContactData() {
    return [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'message' => 'Test message for automated testing.'
    ];
}

// Fonction d'aide pour créer un fichier de configuration de test
function createTestConfig($overrides = []) {
    $defaultConfig = [
        'email' => [
            'contact' => [
                'to' => 'test@example.com',
                'from' => 'noreply@test.com',
                'reply_to' => 'test@example.com',
                'subject_prefix' => '[TEST] '
            ],
            'admin' => [
                'enabled' => true,
                'notify_on_contact' => true,
                'notify_on_upload' => true,
                'notify_on_errors' => true,
                'to' => 'admin@test.com',
                'from' => 'notifications@test.com',
                'subject_prefix' => '[ADMIN TEST] ',
                'format' => 'text'
            ]
        ],
        'upload' => [
            'max_size' => 1024 * 1024, // 1MB pour les tests
            'max_files' => 3,
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
            'upload_dir' => TEST_UPLOADS . '/contact/'
        ],
        'security' => [
            'max_attempts' => 3,
            'cooldown' => 60,
            'honeypot_field' => 'website'
        ]
    ];
    
    $config = array_merge_recursive($defaultConfig, $overrides);
    
    $configPath = TEST_TEMP . '/test-settings.json';
    file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
    
    return $configPath;
}

// Mock de la fonction mail() pour les tests
if (!function_exists('mail')) {
    function mail($to, $subject, $message, $headers = '', $parameters = '') {
        // Enregistrer les données d'email pour les tests
        $emailData = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'parameters' => $parameters,
            'timestamp' => time()
        ];
        
        $logFile = TEST_TEMP . '/test-emails.log';
        file_put_contents($logFile, json_encode($emailData) . "\n", FILE_APPEND);
        
        return true; // Simuler un succès
    }
}

// Fonction d'aide pour récupérer les emails de test
function getTestEmails() {
    $logFile = TEST_TEMP . '/test-emails.log';
    if (!file_exists($logFile)) {
        return [];
    }
    
    $emails = [];
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        if (trim($line)) {
            $emails[] = json_decode($line, true);
        }
    }
    
    return $emails;
}

// Fonction d'aide pour nettoyer les emails de test
function clearTestEmails() {
    $logFile = TEST_TEMP . '/test-emails.log';
    if (file_exists($logFile)) {
        unlink($logFile);
    }
}

// Nettoyage au démarrage des tests
register_shutdown_function('cleanTestDirectories');

// Message de confirmation
echo "Bootstrap de test chargé avec succès\n";
echo "Répertoire de test : " . TEST_ROOT . "\n";
echo "Mode test activé\n";