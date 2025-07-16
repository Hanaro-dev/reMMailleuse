<?php
/**
 * ===== GESTIONNAIRE CDN - SITE REMMAILLEUSE =====
 * Gestion des assets via CDN avec fallback local
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class CDNManager {
    private $config;
    private $cache;
    private $fallbackEnabled;
    private $stats;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'cdn_url' => 'https://cdn.remmailleuse.ch',
            'fallback_enabled' => true,
            'cache_ttl' => 3600, // 1 heure
            'timeout' => 5, // 5 secondes
            'retry_attempts' => 3,
            'asset_types' => ['css', 'js', 'images', 'fonts'],
            'version' => '1.0.0',
            'enable_webp' => true,
            'enable_avif' => false,
            'enable_compression' => true
        ], $config);
        
        $this->cache = [];
        $this->fallbackEnabled = $this->config['fallback_enabled'];
        $this->stats = [
            'cdn_hits' => 0,
            'cdn_misses' => 0,
            'fallback_uses' => 0,
            'errors' => 0
        ];
    }
    
    /**
     * Obtenir l'URL CDN d'un asset
     */
    public function getAssetUrl($path, $options = []) {
        $options = array_merge([
            'version' => $this->config['version'],
            'format' => null,
            'quality' => null,
            'width' => null,
            'height' => null,
            'fallback' => $this->fallbackEnabled
        ], $options);
        
        // Construire l'URL CDN
        $cdnUrl = $this->buildCDNUrl($path, $options);
        
        // Vérifier la disponibilité du CDN
        if ($options['fallback'] && !$this->isCDNAvailable($cdnUrl)) {
            $this->stats['fallback_uses']++;
            return $this->getFallbackUrl($path);
        }
        
        $this->stats['cdn_hits']++;
        return $cdnUrl;
    }
    
    /**
     * Construire l'URL CDN
     */
    private function buildCDNUrl($path, $options) {
        $path = ltrim($path, '/');
        $cdnUrl = rtrim($this->config['cdn_url'], '/') . '/' . $path;
        
        // Ajouter les paramètres de transformation
        $params = [];
        
        if ($options['version']) {
            $params['v'] = $options['version'];
        }
        
        // Transformations d'images
        if ($this->isImagePath($path)) {
            if ($options['format']) {
                $params['format'] = $options['format'];
            } elseif ($this->config['enable_webp'] && $this->supportsWebP()) {
                $params['format'] = 'webp';
            } elseif ($this->config['enable_avif'] && $this->supportsAVIF()) {
                $params['format'] = 'avif';
            }
            
            if ($options['quality']) {
                $params['quality'] = $options['quality'];
            }
            
            if ($options['width']) {
                $params['width'] = $options['width'];
            }
            
            if ($options['height']) {
                $params['height'] = $options['height'];
            }
        }
        
        // Compression
        if ($this->config['enable_compression'] && $this->supportsCompression()) {
            $params['compress'] = 'true';
        }
        
        // Ajouter les paramètres à l'URL
        if (!empty($params)) {
            $cdnUrl .= '?' . http_build_query($params);
        }
        
        return $cdnUrl;
    }
    
    /**
     * Vérifier si un chemin est une image
     */
    private function isImagePath($path) {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }
    
    /**
     * Vérifier si le navigateur supporte WebP
     */
    private function supportsWebP() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'image/webp') !== false;
    }
    
    /**
     * Vérifier si le navigateur supporte AVIF
     */
    private function supportsAVIF() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'image/avif') !== false;
    }
    
    /**
     * Vérifier si le navigateur supporte la compression
     */
    private function supportsCompression() {
        $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        return strpos($encoding, 'gzip') !== false || strpos($encoding, 'br') !== false;
    }
    
    /**
     * Vérifier la disponibilité du CDN
     */
    private function isCDNAvailable($url) {
        $cacheKey = 'cdn_check_' . md5($url);
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() < $cached['expires']) {
                return $cached['available'];
            }
        }
        
        // Vérification rapide de la disponibilité
        $available = $this->checkCDNHealth();
        
        // Mettre en cache le résultat
        $this->cache[$cacheKey] = [
            'available' => $available,
            'expires' => time() + $this->config['cache_ttl']
        ];
        
        return $available;
    }
    
    /**
     * Vérifier l'état de santé du CDN
     */
    private function checkCDNHealth() {
        $healthUrl = $this->config['cdn_url'] . '/health';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config['timeout'],
                'method' => 'HEAD',
                'header' => 'User-Agent: ReMmailleuse CDN Check'
            ]
        ]);
        
        $headers = @get_headers($healthUrl, 1, $context);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        }
        
        $this->stats['errors']++;
        return false;
    }
    
    /**
     * Obtenir l'URL de fallback local
     */
    private function getFallbackUrl($path) {
        return '/' . ltrim($path, '/');
    }
    
    /**
     * Obtenir les URLs avec fallback automatique
     */
    public function getAssetUrls($paths, $options = []) {
        $urls = [];
        
        foreach ($paths as $path) {
            $urls[$path] = $this->getAssetUrl($path, $options);
        }
        
        return $urls;
    }
    
    /**
     * Générer les balises HTML pour les assets
     */
    public function generateAssetTags($assets, $options = []) {
        $tags = [];
        
        foreach ($assets as $asset) {
            $path = $asset['path'];
            $type = $asset['type'] ?? $this->detectAssetType($path);
            $attributes = $asset['attributes'] ?? [];
            
            $url = $this->getAssetUrl($path, $options);
            
            switch ($type) {
                case 'css':
                    $tags[] = $this->generateCSSTag($url, $attributes);
                    break;
                case 'js':
                    $tags[] = $this->generateJSTag($url, $attributes);
                    break;
                case 'image':
                    $tags[] = $this->generateImageTag($url, $attributes);
                    break;
                case 'font':
                    $tags[] = $this->generateFontTag($url, $attributes);
                    break;
            }
        }
        
        return $tags;
    }
    
    /**
     * Détecter le type d'asset
     */
    private function detectAssetType($path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'css':
                return 'css';
            case 'js':
                return 'js';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'avif':
            case 'svg':
                return 'image';
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'otf':
                return 'font';
            default:
                return 'other';
        }
    }
    
    /**
     * Générer une balise CSS
     */
    private function generateCSSTag($url, $attributes) {
        $attrs = array_merge([
            'rel' => 'stylesheet',
            'href' => $url
        ], $attributes);
        
        return '<link' . $this->buildAttributes($attrs) . '>';
    }
    
    /**
     * Générer une balise JS
     */
    private function generateJSTag($url, $attributes) {
        $attrs = array_merge([
            'src' => $url,
            'defer' => true
        ], $attributes);
        
        return '<script' . $this->buildAttributes($attrs) . '></script>';
    }
    
    /**
     * Générer une balise image
     */
    private function generateImageTag($url, $attributes) {
        $attrs = array_merge([
            'src' => $url,
            'loading' => 'lazy'
        ], $attributes);
        
        return '<img' . $this->buildAttributes($attrs) . '>';
    }
    
    /**
     * Générer une balise font
     */
    private function generateFontTag($url, $attributes) {
        $attrs = array_merge([
            'rel' => 'preload',
            'href' => $url,
            'as' => 'font',
            'type' => 'font/woff2',
            'crossorigin' => 'anonymous'
        ], $attributes);
        
        return '<link' . $this->buildAttributes($attrs) . '>';
    }
    
    /**
     * Construire les attributs HTML
     */
    private function buildAttributes($attributes) {
        $attrs = [];
        
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $attrs[] = $key;
            } elseif ($value !== false && $value !== null) {
                $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        return $attrs ? ' ' . implode(' ', $attrs) : '';
    }
    
    /**
     * Précharger des assets
     */
    public function preloadAssets($assets, $options = []) {
        $preloadTags = [];
        
        foreach ($assets as $asset) {
            $path = $asset['path'];
            $type = $asset['type'] ?? $this->detectAssetType($path);
            $priority = $asset['priority'] ?? 'low';
            
            $url = $this->getAssetUrl($path, $options);
            
            $attrs = [
                'rel' => 'preload',
                'href' => $url,
                'as' => $this->getPreloadAs($type)
            ];
            
            if ($priority === 'high') {
                $attrs['fetchpriority'] = 'high';
            }
            
            if ($type === 'font') {
                $attrs['crossorigin'] = 'anonymous';
            }
            
            $preloadTags[] = '<link' . $this->buildAttributes($attrs) . '>';
        }
        
        return $preloadTags;
    }
    
    /**
     * Obtenir la valeur 'as' pour le preload
     */
    private function getPreloadAs($type) {
        switch ($type) {
            case 'css':
                return 'style';
            case 'js':
                return 'script';
            case 'image':
                return 'image';
            case 'font':
                return 'font';
            default:
                return 'fetch';
        }
    }
    
    /**
     * Générer les resource hints
     */
    public function generateResourceHints() {
        $hints = [];
        
        // DNS prefetch
        $hints[] = '<link rel="dns-prefetch" href="' . $this->config['cdn_url'] . '">';
        
        // Preconnect
        $hints[] = '<link rel="preconnect" href="' . $this->config['cdn_url'] . '">';
        
        return $hints;
    }
    
    /**
     * Invalider le cache CDN
     */
    public function invalidateCache($paths = []) {
        $invalidatedPaths = [];
        
        foreach ($paths as $path) {
            $invalidateUrl = $this->config['cdn_url'] . '/invalidate';
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode(['path' => $path])
                ]
            ]);
            
            $result = @file_get_contents($invalidateUrl, false, $context);
            
            if ($result !== false) {
                $invalidatedPaths[] = $path;
            }
        }
        
        return $invalidatedPaths;
    }
    
    /**
     * Obtenir les statistiques
     */
    public function getStats() {
        $total = $this->stats['cdn_hits'] + $this->stats['cdn_misses'] + $this->stats['fallback_uses'];
        
        return [
            'cdn_hits' => $this->stats['cdn_hits'],
            'cdn_misses' => $this->stats['cdn_misses'],
            'fallback_uses' => $this->stats['fallback_uses'],
            'errors' => $this->stats['errors'],
            'total_requests' => $total,
            'cdn_hit_rate' => $total > 0 ? round(($this->stats['cdn_hits'] / $total) * 100, 2) : 0,
            'fallback_rate' => $total > 0 ? round(($this->stats['fallback_uses'] / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Tester la latence du CDN
     */
    public function testLatency() {
        $testUrl = $this->config['cdn_url'] . '/ping';
        
        $attempts = 3;
        $latencies = [];
        
        for ($i = 0; $i < $attempts; $i++) {
            $start = microtime(true);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => $this->config['timeout']
                ]
            ]);
            
            $result = @file_get_contents($testUrl, false, $context);
            
            if ($result !== false) {
                $latencies[] = (microtime(true) - $start) * 1000; // en ms
            }
        }
        
        if (empty($latencies)) {
            return null;
        }
        
        return [
            'min' => min($latencies),
            'max' => max($latencies),
            'avg' => array_sum($latencies) / count($latencies),
            'attempts' => $attempts,
            'successful' => count($latencies)
        ];
    }
    
    /**
     * Configurer les headers de cache
     */
    public function setCacheHeaders($maxAge = 31536000) { // 1 an par défaut
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Vary: Accept-Encoding');
        
        // ETag basé sur le timestamp du fichier
        $etag = '"' . md5($this->config['version']) . '"';
        header('ETag: ' . $etag);
        
        // Vérifier If-None-Match
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            exit;
        }
    }
    
    /**
     * Activer/désactiver le fallback
     */
    public function setFallbackEnabled($enabled) {
        $this->fallbackEnabled = $enabled;
    }
    
    /**
     * Obtenir la configuration actuelle
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Mettre à jour la configuration
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
    }
}

/**
 * Instance globale pour faciliter l'usage
 */
function getCDN($config = []) {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new CDNManager($config);
    }
    
    return $instance;
}

/**
 * Helpers pour les vues
 */
function cdn_url($path, $options = []) {
    return getCDN()->getAssetUrl($path, $options);
}

function cdn_css($path, $attributes = []) {
    $cdn = getCDN();
    $url = $cdn->getAssetUrl($path);
    return $cdn->generateCSSTag($url, $attributes);
}

function cdn_js($path, $attributes = []) {
    $cdn = getCDN();
    $url = $cdn->getAssetUrl($path);
    return $cdn->generateJSTag($url, $attributes);
}

function cdn_img($path, $attributes = []) {
    $cdn = getCDN();
    $url = $cdn->getAssetUrl($path);
    return $cdn->generateImageTag($url, $attributes);
}

?>