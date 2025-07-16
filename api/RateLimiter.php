<?php
/**
 * ===== SYSTÈME DE RATE LIMITING GLOBAL - SITE REMMAILLEUSE =====
 * Rate limiting centralisé pour toutes les APIs avec protection avancée
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class RateLimiter {
    private $tempDir;
    private $config;
    private $cache;
    private $stats;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'temp_dir' => dirname(__DIR__) . '/temp/',
            'default_limit' => 100,
            'default_window' => 3600, // 1 heure
            'cleanup_probability' => 0.1, // 10% de chance
            'enable_progressive_delay' => true,
            'enable_whitelist' => true,
            'enable_blacklist' => true,
            'enable_logging' => true,
            'log_file' => dirname(__DIR__) . '/logs/rate_limit.log',
            'whitelist_ips' => [],
            'blacklist_ips' => [],
            'rules' => []
        ], $config);
        
        $this->tempDir = $this->config['temp_dir'];
        $this->cache = [];
        $this->stats = [
            'total_requests' => 0,
            'blocked_requests' => 0,
            'allowed_requests' => 0,
            'progressive_delays' => 0
        ];
        
        $this->initializeDirectories();
        $this->loadDefaultRules();
    }
    
    /**
     * Initialiser les répertoires nécessaires
     */
    private function initializeDirectories() {
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        if ($this->config['enable_logging']) {
            $logDir = dirname($this->config['log_file']);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }
    
    /**
     * Charger les règles par défaut
     */
    private function loadDefaultRules() {
        $this->config['rules'] = array_merge([
            'auth' => [
                'limit' => 5,
                'window' => 900, // 15 minutes
                'progressive' => true,
                'block_duration' => 1800 // 30 minutes
            ],
            'contact' => [
                'limit' => 3,
                'window' => 300, // 5 minutes
                'progressive' => true,
                'block_duration' => 600 // 10 minutes
            ],
            'upload' => [
                'limit' => 10,
                'window' => 3600, // 1 heure
                'progressive' => false,
                'block_duration' => 3600 // 1 heure
            ],
            'api' => [
                'limit' => 60,
                'window' => 60, // 1 minute
                'progressive' => false,
                'block_duration' => 300 // 5 minutes
            ],
            'general' => [
                'limit' => 200,
                'window' => 3600, // 1 heure
                'progressive' => false,
                'block_duration' => 600 // 10 minutes
            ]
        ], $this->config['rules']);
    }
    
    /**
     * Vérifier si une requête est autorisée
     */
    public function isAllowed($identifier, $rule = 'general', $context = []) {
        $this->stats['total_requests']++;
        
        // Nettoyer périodiquement
        if (mt_rand(1, 1000) <= ($this->config['cleanup_probability'] * 1000)) {
            $this->cleanup();
        }
        
        // Vérifier la whitelist
        if ($this->config['enable_whitelist'] && $this->isWhitelisted($identifier)) {
            $this->stats['allowed_requests']++;
            return [
                'allowed' => true,
                'reason' => 'whitelisted',
                'remaining' => null,
                'reset_time' => null
            ];
        }
        
        // Vérifier la blacklist
        if ($this->config['enable_blacklist'] && $this->isBlacklisted($identifier)) {
            $this->stats['blocked_requests']++;
            $this->logRequest($identifier, $rule, 'blocked', 'blacklisted', $context);
            return [
                'allowed' => false,
                'reason' => 'blacklisted',
                'remaining' => 0,
                'reset_time' => null
            ];
        }
        
        // Obtenir la règle
        $ruleConfig = $this->config['rules'][$rule] ?? $this->config['rules']['general'];
        
        // Vérifier le rate limit
        $result = $this->checkRateLimit($identifier, $rule, $ruleConfig, $context);
        
        if ($result['allowed']) {
            $this->stats['allowed_requests']++;
            $this->logRequest($identifier, $rule, 'allowed', 'within_limit', $context);
        } else {
            $this->stats['blocked_requests']++;
            $this->logRequest($identifier, $rule, 'blocked', $result['reason'], $context);
        }
        
        return $result;
    }
    
    /**
     * Vérifier le rate limit
     */
    private function checkRateLimit($identifier, $rule, $ruleConfig, $context) {
        $key = $this->generateKey($identifier, $rule);
        $now = time();
        
        // Charger les données
        $data = $this->loadRateLimitData($key);
        
        // Vérifier si l'utilisateur est bloqué
        if (isset($data['blocked_until']) && $data['blocked_until'] > $now) {
            return [
                'allowed' => false,
                'reason' => 'temporarily_blocked',
                'remaining' => 0,
                'reset_time' => $data['blocked_until'],
                'block_duration' => $data['blocked_until'] - $now
            ];
        }
        
        // Nettoyer les anciens enregistrements
        $windowStart = $now - $ruleConfig['window'];
        $data['requests'] = array_filter($data['requests'] ?? [], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Vérifier la limite
        $currentCount = count($data['requests']);
        
        if ($currentCount >= $ruleConfig['limit']) {
            // Limite dépassée
            $resetTime = min($data['requests']) + $ruleConfig['window'];
            
            // Bloquer temporairement si configuré
            if (isset($ruleConfig['block_duration'])) {
                $data['blocked_until'] = $now + $ruleConfig['block_duration'];
                $data['block_count'] = ($data['block_count'] ?? 0) + 1;
                
                // Délai progressif
                if ($ruleConfig['progressive'] && $this->config['enable_progressive_delay']) {
                    $progressiveDelay = $this->calculateProgressiveDelay($data['block_count']);
                    $data['blocked_until'] += $progressiveDelay;
                    $this->stats['progressive_delays']++;
                }
            }
            
            $this->saveRateLimitData($key, $data);
            
            return [
                'allowed' => false,
                'reason' => 'rate_limit_exceeded',
                'remaining' => 0,
                'reset_time' => $resetTime,
                'block_duration' => isset($data['blocked_until']) ? $data['blocked_until'] - $now : 0
            ];
        }
        
        // Ajouter la requête
        $data['requests'][] = $now;
        $data['last_request'] = $now;
        $data['total_requests'] = ($data['total_requests'] ?? 0) + 1;
        
        $this->saveRateLimitData($key, $data);
        
        return [
            'allowed' => true,
            'reason' => 'within_limit',
            'remaining' => $ruleConfig['limit'] - $currentCount - 1,
            'reset_time' => min($data['requests']) + $ruleConfig['window']
        ];
    }
    
    /**
     * Calculer le délai progressif
     */
    private function calculateProgressiveDelay($blockCount) {
        // Délai exponentiel : 2^(blockCount-1) minutes, max 1 heure
        $delayMinutes = min(pow(2, $blockCount - 1), 60);
        return $delayMinutes * 60;
    }
    
    /**
     * Vérifier si un identifiant est en whitelist
     */
    private function isWhitelisted($identifier) {
        return in_array($identifier, $this->config['whitelist_ips']);
    }
    
    /**
     * Vérifier si un identifiant est en blacklist
     */
    private function isBlacklisted($identifier) {
        return in_array($identifier, $this->config['blacklist_ips']);
    }
    
    /**
     * Générer une clé de cache
     */
    private function generateKey($identifier, $rule) {
        return 'rate_limit_' . md5($identifier . '_' . $rule);
    }
    
    /**
     * Charger les données de rate limit
     */
    private function loadRateLimitData($key) {
        $file = $this->tempDir . $key . '.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data !== null) {
                return $data;
            }
        }
        
        return [
            'requests' => [],
            'total_requests' => 0,
            'block_count' => 0,
            'created' => time()
        ];
    }
    
    /**
     * Sauvegarder les données de rate limit
     */
    private function saveRateLimitData($key, $data) {
        $file = $this->tempDir . $key . '.json';
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Ajouter une IP à la whitelist
     */
    public function addToWhitelist($ip) {
        if (!in_array($ip, $this->config['whitelist_ips'])) {
            $this->config['whitelist_ips'][] = $ip;
            $this->saveWhitelist();
        }
    }
    
    /**
     * Retirer une IP de la whitelist
     */
    public function removeFromWhitelist($ip) {
        $this->config['whitelist_ips'] = array_values(array_filter(
            $this->config['whitelist_ips'],
            function($whitelistIp) use ($ip) {
                return $whitelistIp !== $ip;
            }
        ));
        $this->saveWhitelist();
    }
    
    /**
     * Ajouter une IP à la blacklist
     */
    public function addToBlacklist($ip, $reason = 'manual') {
        if (!in_array($ip, $this->config['blacklist_ips'])) {
            $this->config['blacklist_ips'][] = $ip;
            $this->saveBlacklist();
            $this->logRequest($ip, 'blacklist', 'added', $reason, ['manual' => true]);
        }
    }
    
    /**
     * Retirer une IP de la blacklist
     */
    public function removeFromBlacklist($ip) {
        $this->config['blacklist_ips'] = array_values(array_filter(
            $this->config['blacklist_ips'],
            function($blacklistIp) use ($ip) {
                return $blacklistIp !== $ip;
            }
        ));
        $this->saveBlacklist();
    }
    
    /**
     * Sauvegarder la whitelist
     */
    private function saveWhitelist() {
        $file = $this->tempDir . 'whitelist.json';
        file_put_contents($file, json_encode($this->config['whitelist_ips']), LOCK_EX);
    }
    
    /**
     * Sauvegarder la blacklist
     */
    private function saveBlacklist() {
        $file = $this->tempDir . 'blacklist.json';
        file_put_contents($file, json_encode($this->config['blacklist_ips']), LOCK_EX);
    }
    
    /**
     * Débloquer un identifiant
     */
    public function unblock($identifier, $rule = 'general') {
        $key = $this->generateKey($identifier, $rule);
        $data = $this->loadRateLimitData($key);
        
        unset($data['blocked_until']);
        $data['requests'] = [];
        
        $this->saveRateLimitData($key, $data);
        
        $this->logRequest($identifier, $rule, 'unblocked', 'manual', ['manual' => true]);
    }
    
    /**
     * Obtenir les statistiques d'un identifiant
     */
    public function getStats($identifier = null, $rule = null) {
        if ($identifier && $rule) {
            $key = $this->generateKey($identifier, $rule);
            $data = $this->loadRateLimitData($key);
            $ruleConfig = $this->config['rules'][$rule] ?? $this->config['rules']['general'];
            
            $windowStart = time() - $ruleConfig['window'];
            $recentRequests = array_filter($data['requests'] ?? [], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });
            
            return [
                'identifier' => $identifier,
                'rule' => $rule,
                'current_count' => count($recentRequests),
                'limit' => $ruleConfig['limit'],
                'window' => $ruleConfig['window'],
                'remaining' => max(0, $ruleConfig['limit'] - count($recentRequests)),
                'blocked_until' => $data['blocked_until'] ?? null,
                'block_count' => $data['block_count'] ?? 0,
                'total_requests' => $data['total_requests'] ?? 0,
                'last_request' => $data['last_request'] ?? null
            ];
        }
        
        return [
            'global' => $this->stats,
            'rules' => $this->config['rules'],
            'whitelist_count' => count($this->config['whitelist_ips']),
            'blacklist_count' => count($this->config['blacklist_ips']),
            'active_limits' => $this->getActiveLimits()
        ];
    }
    
    /**
     * Obtenir les limites actives
     */
    private function getActiveLimits() {
        $files = glob($this->tempDir . 'rate_limit_*.json');
        $activeLimits = [];
        $now = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && (!empty($data['requests']) || isset($data['blocked_until']))) {
                $filename = basename($file, '.json');
                $activeLimits[] = [
                    'key' => $filename,
                    'requests_count' => count($data['requests'] ?? []),
                    'blocked_until' => $data['blocked_until'] ?? null,
                    'is_blocked' => isset($data['blocked_until']) && $data['blocked_until'] > $now,
                    'last_request' => $data['last_request'] ?? null
                ];
            }
        }
        
        return $activeLimits;
    }
    
    /**
     * Logger une requête
     */
    private function logRequest($identifier, $rule, $action, $reason, $context = []) {
        if (!$this->config['enable_logging']) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'identifier' => $identifier,
            'rule' => $rule,
            'action' => $action,
            'reason' => $reason,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->config['log_file'], $logLine, FILE_APPEND | LOCK_EX);
        
        // Rotation des logs si nécessaire
        $this->rotateLogIfNeeded();
    }
    
    /**
     * Rotation des logs
     */
    private function rotateLogIfNeeded() {
        $logFile = $this->config['log_file'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file_exists($logFile) && filesize($logFile) > $maxSize) {
            $rotatedFile = $logFile . '.' . date('Y-m-d-H-i-s');
            rename($logFile, $rotatedFile);
            
            // Garder seulement les 10 derniers logs
            $rotatedFiles = glob($logFile . '.*');
            if (count($rotatedFiles) > 10) {
                usort($rotatedFiles, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                $toDelete = array_slice($rotatedFiles, 0, count($rotatedFiles) - 10);
                foreach ($toDelete as $file) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Nettoyer les anciens fichiers
     */
    public function cleanup() {
        $files = glob($this->tempDir . 'rate_limit_*.json');
        $now = time();
        $maxAge = 7 * 24 * 60 * 60; // 7 jours
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data) {
                unlink($file);
                $cleaned++;
                continue;
            }
            
            // Supprimer si trop ancien et pas de requêtes récentes
            $lastActivity = max(
                $data['last_request'] ?? 0,
                $data['blocked_until'] ?? 0,
                max($data['requests'] ?? [0])
            );
            
            if ($lastActivity < ($now - $maxAge)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            $this->logRequest('system', 'cleanup', 'cleaned', 'old_files', ['files_cleaned' => $cleaned]);
        }
        
        return $cleaned;
    }
    
    /**
     * Réinitialiser toutes les limites
     */
    public function resetAll() {
        $files = glob($this->tempDir . 'rate_limit_*.json');
        $reset = 0;
        
        foreach ($files as $file) {
            unlink($file);
            $reset++;
        }
        
        $this->stats = [
            'total_requests' => 0,
            'blocked_requests' => 0,
            'allowed_requests' => 0,
            'progressive_delays' => 0
        ];
        
        $this->logRequest('system', 'reset', 'reset_all', 'manual', ['files_reset' => $reset]);
        
        return $reset;
    }
    
    /**
     * Obtenir les rapports détaillés
     */
    public function getDetailedReport() {
        $report = [
            'summary' => $this->getStats(),
            'rules' => $this->config['rules'],
            'whitelist' => $this->config['whitelist_ips'],
            'blacklist' => $this->config['blacklist_ips'],
            'active_limits' => $this->getActiveLimits(),
            'recent_blocks' => $this->getRecentBlocks(),
            'top_blocked_ips' => $this->getTopBlockedIPs()
        ];
        
        return $report;
    }
    
    /**
     * Obtenir les blocages récents
     */
    private function getRecentBlocks() {
        $files = glob($this->tempDir . 'rate_limit_*.json');
        $blocks = [];
        $now = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['blocked_until']) && $data['blocked_until'] > $now) {
                $blocks[] = [
                    'key' => basename($file, '.json'),
                    'blocked_until' => $data['blocked_until'],
                    'block_count' => $data['block_count'] ?? 0,
                    'total_requests' => $data['total_requests'] ?? 0
                ];
            }
        }
        
        return $blocks;
    }
    
    /**
     * Obtenir les IPs les plus bloquées
     */
    private function getTopBlockedIPs() {
        $files = glob($this->tempDir . 'rate_limit_*.json');
        $ipStats = [];
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['block_count']) && $data['block_count'] > 0) {
                $key = basename($file, '.json');
                $ipStats[$key] = $data['block_count'];
            }
        }
        
        arsort($ipStats);
        return array_slice($ipStats, 0, 10, true);
    }
}

/**
 * Instance globale pour faciliter l'usage
 */
function getRateLimiter($config = []) {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new RateLimiter($config);
    }
    
    return $instance;
}

/**
 * Fonction helper pour vérifier rapidement une limite
 */
function checkRateLimit($identifier, $rule = 'general', $context = []) {
    return getRateLimiter()->isAllowed($identifier, $rule, $context);
}

/**
 * Fonction helper pour débloquer un identifiant
 */
function unblockRateLimit($identifier, $rule = 'general') {
    return getRateLimiter()->unblock($identifier, $rule);
}

?>