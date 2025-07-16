<?php
/**
 * Security Headers Manager for ReMmailleuse
 * 
 * Gère les en-têtes de sécurité avancés selon les bonnes pratiques
 * OWASP et les standards de sécurité web modernes
 */

class SecurityHeaders {
    private $config;
    private $logger;
    
    public function __construct($logger = null) {
        $this->logger = $logger;
        
        // Configuration CSP adaptée selon le contexte
        $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
        
        $this->config = [
            'csp_policy' => [
                'default-src' => "'self'",
                'script-src' => $isAdmin ? "'self' 'unsafe-inline' 'unsafe-eval'" : "'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                'style-src' => $isAdmin ? "'self' 'unsafe-inline' https://fonts.googleapis.com" : "'self' 'unsafe-inline' https://fonts.googleapis.com",
                'img-src' => "'self' data: https: blob:",
                'font-src' => "'self' https://fonts.gstatic.com",
                'connect-src' => "'self'",
                'media-src' => "'self'",
                'object-src' => "'none'",
                'frame-src' => "'none'",
                'base-uri' => "'self'",
                'form-action' => "'self'",
                'upgrade-insecure-requests' => true
            ],
            'security_headers' => [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), fullscreen=(self), sync-xhr=()',
                'Cross-Origin-Embedder-Policy' => 'require-corp',
                'Cross-Origin-Opener-Policy' => 'same-origin',
                'Cross-Origin-Resource-Policy' => 'same-site'
            ],
            'hsts' => [
                'enabled' => true,
                'max_age' => 31536000, // 1 an
                'include_subdomains' => true,
                'preload' => true
            ],
            'cache_control' => [
                'no_cache_pages' => [
                    '/admin/',
                    '/api/',
                    '/login'
                ],
                'cache_pages' => [
                    '/assets/',
                    '/images/',
                    '/css/',
                    '/js/'
                ]
            ]
        ];
    }
    
    /**
     * Appliquer tous les en-têtes de sécurité
     */
    public function applySecurityHeaders() {
        // Vérifier si les en-têtes n'ont pas déjà été envoyés
        if (headers_sent()) {
            if ($this->logger) {
                $this->logger->warning('Impossible d\'appliquer les en-têtes de sécurité - déjà envoyés', [], 'security');
            }
            return false;
        }
        
        $this->setCSPHeaders();
        $this->setSecurityHeaders();
        $this->setHSTSHeaders();
        $this->setCacheControlHeaders();
        
        if ($this->logger) {
            $this->logger->info('En-têtes de sécurité appliqués', [
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ], 'security');
        }
        
        return true;
    }
    
    /**
     * Définir les en-têtes CSP
     */
    private function setCSPHeaders() {
        $csp = [];
        
        foreach ($this->config['csp_policy'] as $directive => $value) {
            if ($directive === 'upgrade-insecure-requests' && $value) {
                $csp[] = 'upgrade-insecure-requests';
            } else {
                $csp[] = "$directive $value";
            }
        }
        
        $cspString = implode('; ', $csp);
        
        // CSP principale
        header("Content-Security-Policy: $cspString");
        
        // CSP en mode report-only pour les tests
        if (defined('CSP_REPORT_ONLY') && CSP_REPORT_ONLY) {
            header("Content-Security-Policy-Report-Only: $cspString");
        }
    }
    
    /**
     * Définir les en-têtes de sécurité standard
     */
    private function setSecurityHeaders() {
        foreach ($this->config['security_headers'] as $header => $value) {
            header("$header: $value");
        }
    }
    
    /**
     * Définir les en-têtes HSTS
     */
    private function setHSTSHeaders() {
        if (!$this->config['hsts']['enabled']) {
            return;
        }
        
        // Vérifier si on est en HTTPS
        if (!$this->isHTTPS()) {
            if ($this->logger) {
                $this->logger->warning('HSTS non appliqué - connexion non HTTPS', [], 'security');
            }
            return;
        }
        
        $hsts = 'max-age=' . $this->config['hsts']['max_age'];
        
        if ($this->config['hsts']['include_subdomains']) {
            $hsts .= '; includeSubDomains';
        }
        
        if ($this->config['hsts']['preload']) {
            $hsts .= '; preload';
        }
        
        header("Strict-Transport-Security: $hsts");
    }
    
    /**
     * Définir les en-têtes de contrôle de cache
     */
    private function setCacheControlHeaders() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Pages sans cache
        foreach ($this->config['cache_control']['no_cache_pages'] as $path) {
            if (strpos($requestUri, $path) === 0) {
                header('Cache-Control: no-cache, no-store, must-revalidate, private');
                header('Pragma: no-cache');
                header('Expires: 0');
                return;
            }
        }
        
        // Pages avec cache
        foreach ($this->config['cache_control']['cache_pages'] as $path) {
            if (strpos($requestUri, $path) === 0) {
                header('Cache-Control: public, max-age=3600'); // 1 heure
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                return;
            }
        }
        
        // Cache par défaut
        header('Cache-Control: public, max-age=1800'); // 30 minutes
    }
    
    /**
     * Vérifier si la connexion est HTTPS
     */
    private function isHTTPS() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        );
    }
    
    /**
     * Générer un nonce pour les scripts inline
     */
    public function generateNonce() {
        $nonce = base64_encode(random_bytes(16));
        
        // Stocker le nonce pour cette requête
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['csp_nonce'] = $nonce;
        
        return $nonce;
    }
    
    /**
     * Obtenir le nonce actuel
     */
    public function getNonce() {
        if (!isset($_SESSION)) {
            session_start();
        }
        return $_SESSION['csp_nonce'] ?? null;
    }
    
    /**
     * Configurer CSP pour une page spécifique
     */
    public function setPageCSP($pageType) {
        $customCSP = $this->config['csp_policy'];
        
        switch ($pageType) {
            case 'admin':
                // Pour l'admin, on garde 'unsafe-inline' sans nonce pour permettre onclick
                $customCSP['script-src'] = "'self' 'unsafe-inline' 'unsafe-eval'";
                $customCSP['style-src'] = "'self' 'unsafe-inline' https://fonts.googleapis.com";
                $customCSP['font-src'] = "'self' https://fonts.gstatic.com";
                break;
                
            case 'upload':
                $customCSP['form-action'] = "'self'";
                $customCSP['connect-src'] .= " blob:";
                break;
                
            case 'api':
                $customCSP['default-src'] = "'self'";
                $customCSP['script-src'] = "'none'";
                $customCSP['style-src'] = "'none'";
                break;
        }
        
        $this->config['csp_policy'] = $customCSP;
    }
    
    /**
     * Valider la configuration de sécurité
     */
    public function validateSecurityConfig() {
        $issues = [];
        
        // Vérifier HTTPS
        if (!$this->isHTTPS()) {
            $issues[] = 'Connexion non sécurisée (HTTP au lieu de HTTPS)';
        }
        
        // Vérifier la configuration CSP
        if (empty($this->config['csp_policy']['default-src'])) {
            $issues[] = 'CSP default-src non configuré';
        }
        
        // Vérifier les en-têtes critiques
        $criticalHeaders = ['X-Content-Type-Options', 'X-Frame-Options', 'X-XSS-Protection'];
        foreach ($criticalHeaders as $header) {
            if (!isset($this->config['security_headers'][$header])) {
                $issues[] = "En-tête critique manquant: $header";
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => max(0, 100 - (count($issues) * 10))
        ];
    }
    
    /**
     * Générer un rapport de sécurité
     */
    public function generateSecurityReport() {
        $validation = $this->validateSecurityConfig();
        
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'https_enabled' => $this->isHTTPS(),
            'csp_enabled' => !empty($this->config['csp_policy']),
            'hsts_enabled' => $this->config['hsts']['enabled'],
            'headers_count' => count($this->config['security_headers']),
            'validation' => $validation,
            'headers_applied' => $this->getAppliedHeaders()
        ];
    }
    
    /**
     * Obtenir les en-têtes appliqués
     */
    private function getAppliedHeaders() {
        $headers = [];
        
        if (function_exists('headers_list')) {
            $headersList = headers_list();
            foreach ($headersList as $header) {
                if (preg_match('/^(Content-Security-Policy|X-|Strict-Transport-Security|Referrer-Policy|Permissions-Policy|Cross-Origin-)/i', $header)) {
                    $headers[] = $header;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Nettoyer les données d'entrée
     */
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
                
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                
            default:
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Valider les données d'entrée
     */
    public function validateInput($input, $type, $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
                
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
                
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT, $options);
                
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT, $options);
                
            case 'ip':
                return filter_var($input, FILTER_VALIDATE_IP);
                
            case 'regex':
                return isset($options['pattern']) ? preg_match($options['pattern'], $input) : false;
                
            default:
                return !empty($input);
        }
    }
}

// Fonction helper pour initialiser les en-têtes de sécurité
function initSecurityHeaders($pageType = null, $logger = null) {
    $security = new SecurityHeaders($logger);
    
    if ($pageType) {
        $security->setPageCSP($pageType);
    }
    
    return $security->applySecurityHeaders();
}

// Fonction helper pour le nettoyage des données
function sanitize($input, $type = 'string') {
    $security = new SecurityHeaders();
    return $security->sanitizeInput($input, $type);
}

// Fonction helper pour la validation
function validate($input, $type, $options = []) {
    $security = new SecurityHeaders();
    return $security->validateInput($input, $type, $options);
}
?>