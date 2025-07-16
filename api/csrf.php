<?php
/**
 * Système de protection CSRF (Cross-Site Request Forgery)
 * Génère et vérifie les tokens CSRF pour sécuriser les formulaires
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenLifetime = 3600; // 1 heure
    
    /**
     * Génère un token CSRF unique
     */
    public static function generateToken() {
        session_start();
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        // Stocker le token avec timestamp
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'timestamp' => $timestamp
        ];
        
        return $token;
    }
    
    /**
     * Vérifie la validité d'un token CSRF
     */
    public static function verifyToken($token) {
        session_start();
        
        // Vérifier si le token existe en session
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        $storedData = $_SESSION[self::$tokenName];
        
        // Vérifier l'expiration
        if ((time() - $storedData['timestamp']) > self::$tokenLifetime) {
            unset($_SESSION[self::$tokenName]);
            return false;
        }
        
        // Vérifier le token
        if (!hash_equals($storedData['token'], $token)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Génère un token et retourne le HTML du champ hidden
     */
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '" />';
    }
    
    /**
     * Génère un token pour les requêtes AJAX
     */
    public static function getTokenForAjax() {
        $token = self::generateToken();
        return [
            'token' => $token,
            'name' => self::$tokenName
        ];
    }
    
    /**
     * Middleware pour vérifier le token CSRF
     */
    public static function validateRequest() {
        $token = null;
        
        // Récupérer le token depuis POST ou headers
        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        if (!$token || !self::verifyToken($token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Token CSRF invalide ou expiré'
            ]);
            exit();
        }
    }
    
    /**
     * Nettoie les tokens expirés
     */
    public static function cleanupExpiredTokens() {
        session_start();
        
        if (isset($_SESSION[self::$tokenName])) {
            $storedData = $_SESSION[self::$tokenName];
            
            if ((time() - $storedData['timestamp']) > self::$tokenLifetime) {
                unset($_SESSION[self::$tokenName]);
            }
        }
    }
    
    /**
     * Génère un nouveau token (rotation)
     */
    public static function rotateToken() {
        session_start();
        unset($_SESSION[self::$tokenName]);
        return self::generateToken();
    }
}

// Endpoint pour récupérer un token CSRF via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_token') {
    session_start();
    header('Content-Type: application/json; charset=utf-8');
    
    $tokenData = CSRFProtection::getTokenForAjax();
    
    echo json_encode([
        'success' => true,
        'token' => $tokenData['token'],
        'name' => $tokenData['name']
    ]);
    exit();
}

?>