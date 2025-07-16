<?php
/**
 * Système d'authentification pour l'interface d'administration
 * Utilise des sessions PHP et un hash sécurisé du mot de passe
 */

// Inclure le système de protection CSRF
require_once 'csrf.php';
require_once 'RateLimiter.php';
require_once 'Logger.php';
require_once 'SecurityHeaders.php';

session_start();

// Initialiser les en-têtes de sécurité
$logger = new Logger();
initSecurityHeaders('api', $logger);

header('Content-Type: application/json; charset=utf-8');

// Configuration CORS sécurisée pour les requêtes AJAX
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Gestion des requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration de l'authentification - SÉCURISÉE
define('ADMIN_USERNAME', 'admin');
// Hash sécurisé du mot de passe - Production Ready
// IMPORTANT: Changez ce hash en production avec password_hash()
define('ADMIN_PASSWORD_HASH', '$2y$10$rS8wqjK.V1IyFqMJF9hQ8uK3JvJnE5ZYN5VyL7jKM8.QWvqFGdX2K');

// Durée de vie de la session en secondes (2 heures)
define('SESSION_LIFETIME', 7200);

// Protection contre le brute force
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Fonction pour obtenir/créer le fichier de tracking des tentatives
function getAttemptsFile() {
    $tempDir = __DIR__ . '/../temp/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    return $tempDir . 'login_attempts.json';
}

// Fonction pour vérifier si l'IP est bloquée
function isIPLocked($ip) {
    $attemptsFile = getAttemptsFile();
    if (!file_exists($attemptsFile)) {
        return false;
    }
    
    $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
    
    if (isset($attempts[$ip])) {
        $lastAttempt = $attempts[$ip]['last_attempt'];
        $count = $attempts[$ip]['count'];
        
        if ($count >= MAX_LOGIN_ATTEMPTS && (time() - $lastAttempt) < LOCKOUT_TIME) {
            return true;
        }
        
        // Réinitialiser si le délai est passé
        if ((time() - $lastAttempt) >= LOCKOUT_TIME) {
            unset($attempts[$ip]);
            file_put_contents($attemptsFile, json_encode($attempts));
        }
    }
    
    return false;
}

// Fonction pour enregistrer une tentative de connexion
function recordLoginAttempt($ip, $success = false) {
    $attemptsFile = getAttemptsFile();
    $attempts = file_exists($attemptsFile) ? json_decode(file_get_contents($attemptsFile), true) : [];
    
    if ($success) {
        // Supprimer les tentatives en cas de succès
        unset($attempts[$ip]);
    } else {
        // Incrémenter le compteur de tentatives échouées
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
        }
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
    }
    
    file_put_contents($attemptsFile, json_encode($attempts));
}

// Fonction pour nettoyer les anciennes tentatives (1% de chance à chaque requête)
function cleanupOldAttempts() {
    if (rand(1, 100) !== 1) return;
    
    $attemptsFile = getAttemptsFile();
    if (!file_exists($attemptsFile)) return;
    
    $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
    $cleaned = [];
    
    foreach ($attempts as $ip => $data) {
        if ((time() - $data['last_attempt']) < 86400) { // Garder 24h
            $cleaned[$ip] = $data;
        }
    }
    
    file_put_contents($attemptsFile, json_encode($cleaned));
}

// Nettoyer les anciennes tentatives avec le nouveau système
if (rand(1, 20) === 1) { // 5% de chance au lieu de 1%
    require_once 'CleanupManager.php';
    $cleanupManager = new CleanupManager();
    $cleanupManager->runQuickCleanup();
}

// Router les actions
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];

switch ($action) {
    case 'login':
        handleLogin($ip);
        break;
    
    case 'logout':
        handleLogout();
        break;
    
    case 'check':
        handleCheck();
        break;
    
    case 'refresh':
        handleRefresh();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
}

// Gestion de la connexion
function handleLogin($ip) {
    // Vérifier le rate limiting global
    $rateLimiter = getRateLimiter();
    $rateLimitResult = $rateLimiter->isAllowed($ip, 'auth', [
        'endpoint' => 'auth_login',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    if (!$rateLimitResult['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Trop de tentatives de connexion. Réessayez dans quelques minutes.',
            'retry_after' => $rateLimitResult['reset_time'] - time(),
            'reason' => $rateLimitResult['reason']
        ]);
        return;
    }
    
    // Vérifier si l'IP est bloquée (système legacy)
    if (isIPLocked($ip)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Trop de tentatives échouées. Réessayez dans 15 minutes.'
        ]);
        return;
    }
    
    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $csrfToken = $input['csrf_token'] ?? '';
    
    // Valider le token CSRF
    if (!CSRFProtection::verifyToken($csrfToken)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Token CSRF invalide ou expiré'
        ]);
        return;
    }
    
    // Valider les identifiants
    // Production : uniquement hash sécurisé
    $isValidPassword = password_verify($password, ADMIN_PASSWORD_HASH);
    
    if ($username === ADMIN_USERNAME && $isValidPassword) {
        // Connexion réussie
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Régénérer l'ID de session pour la sécurité
        session_regenerate_id(true);
        
        // Enregistrer le succès
        recordLoginAttempt($ip, true);
        
        // Logger le succès de connexion
        logSecurity("Connexion admin réussie", [
            'ip' => $ip,
            'user' => $username,
            'session_id' => session_id(),
            'timestamp' => time()
        ], 'INFO');
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'sessionId' => session_id()
        ]);
    } else {
        // Connexion échouée
        recordLoginAttempt($ip, false);
        
        // Logger la tentative échouée
        logSecurity("Tentative de connexion échouée", [
            'ip' => $ip,
            'user' => $username,
            'timestamp' => time()
        ], 'WARNING');
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Identifiants incorrects'
        ]);
    }
}


// Vérification de la session
function handleCheck() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        // Vérifier si la session n'a pas expiré
        if ((time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            // Session expirée
            $_SESSION = [];
            session_destroy();
            
            echo json_encode([
                'success' => false,
                'authenticated' => false,
                'message' => 'Session expirée'
            ]);
        } else {
            // Session valide - mettre à jour l'activité
            $_SESSION['last_activity'] = time();
            
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'username' => $_SESSION['admin_username'],
                'sessionTime' => time() - $_SESSION['login_time']
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }
}

// Rafraîchir la session
function handleRefresh() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $_SESSION['last_activity'] = time();
        session_regenerate_id(true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Session rafraîchie'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Non authentifié'
        ]);
    }
}

// Gestion de la déconnexion
function handleLogout() {
    // Logger la déconnexion
    if (isset($_SESSION['admin_username'])) {
        logSecurity('Déconnexion admin', [
            'user' => $_SESSION['admin_username'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'session_id' => session_id(),
            'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0
        ], 'INFO');
    }
    
    // Détruire la session
    session_destroy();
    
    // Démarrer une nouvelle session
    session_start();
    
    // Régénérer l'ID de session
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie',
        'redirect' => '/admin/login.html'
    ]);
}

?>