<?php
/**
 * ===== OPTIMISEUR D'ASSETS - SITE REMMAILLEUSE =====
 * Minification et compression des assets CSS/JS
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class AssetOptimizer {
    private $baseDir;
    private $assetsDir;
    private $outputDir;
    private $cacheDir;
    private $enableMinification;
    private $enableCompression;
    private $enableSourceMaps;
    
    public function __construct($config = []) {
        $this->baseDir = $config['base_dir'] ?? dirname(__DIR__);
        $this->assetsDir = $config['assets_dir'] ?? $this->baseDir . '/assets/';
        $this->outputDir = $config['output_dir'] ?? $this->baseDir . '/dist/';
        $this->cacheDir = $config['cache_dir'] ?? $this->baseDir . '/cache/assets/';
        $this->enableMinification = $config['minification'] ?? true;
        $this->enableCompression = $config['compression'] ?? true;
        $this->enableSourceMaps = $config['source_maps'] ?? false;
        
        $this->initializeDirectories();
    }
    
    /**
     * Initialiser les répertoires nécessaires
     */
    private function initializeDirectories() {
        $dirs = [$this->outputDir, $this->cacheDir];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Créer les sous-dossiers
        $subDirs = ['css', 'js', 'images'];
        foreach ($subDirs as $subDir) {
            $path = $this->outputDir . $subDir . '/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Optimiser tous les assets
     */
    public function optimizeAll() {
        $results = [
            'css' => $this->optimizeCSS(),
            'js' => $this->optimizeJS(),
            'images' => $this->optimizeImages()
        ];
        
        return $results;
    }
    
    /**
     * Optimiser les fichiers CSS
     */
    public function optimizeCSS() {
        $cssFiles = glob($this->assetsDir . 'css/*.css');
        $results = [];
        
        foreach ($cssFiles as $file) {
            $result = $this->processCSS($file);
            $results[basename($file)] = $result;
        }
        
        // Créer un bundle CSS combiné
        $bundleResult = $this->createCSSBundle($cssFiles);
        $results['bundle'] = $bundleResult;
        
        return $results;
    }
    
    /**
     * Traiter un fichier CSS
     */
    private function processCSS($file) {
        $fileName = basename($file, '.css');
        $content = file_get_contents($file);
        $originalSize = strlen($content);
        
        // Vérifier le cache
        $cacheKey = md5($content);
        $cachedFile = $this->cacheDir . $fileName . '_' . $cacheKey . '.css';
        
        if (file_exists($cachedFile)) {
            $minifiedContent = file_get_contents($cachedFile);
        } else {
            // Minifier le CSS
            $minifiedContent = $this->minifyCSS($content);
            
            // Sauvegarder dans le cache
            file_put_contents($cachedFile, $minifiedContent);
        }
        
        // Sauvegarder le fichier optimisé
        $outputFile = $this->outputDir . 'css/' . $fileName . '.min.css';
        file_put_contents($outputFile, $minifiedContent);
        
        // Compression gzip
        if ($this->enableCompression) {
            $gzipFile = $outputFile . '.gz';
            file_put_contents($gzipFile, gzencode($minifiedContent, 9));
        }
        
        return [
            'original_size' => $originalSize,
            'minified_size' => strlen($minifiedContent),
            'compression_ratio' => round((1 - strlen($minifiedContent) / $originalSize) * 100, 2),
            'output_file' => $outputFile,
            'gzip_file' => $this->enableCompression ? $gzipFile : null
        ];
    }
    
    /**
     * Minifier le CSS
     */
    private function minifyCSS($css) {
        // Supprimer les commentaires
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Supprimer les espaces multiples
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Supprimer les espaces autour des caractères spéciaux
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Supprimer les point-virgules avant les accolades fermantes
        $css = preg_replace('/;+}/', '}', $css);
        
        // Supprimer les espaces en début et fin
        $css = trim($css);
        
        // Optimisations spécifiques
        $css = str_replace([
            ';}', ' 0px', ' 0em', ' 0%', ':0px', ':0em', ':0%',
            'margin:0 0 0 0', 'padding:0 0 0 0'
        ], [
            '}', ' 0', ' 0', ' 0', ':0', ':0', ':0',
            'margin:0', 'padding:0'
        ], $css);
        
        return $css;
    }
    
    /**
     * Créer un bundle CSS
     */
    private function createCSSBundle($cssFiles) {
        $bundleContent = '';
        $totalOriginalSize = 0;
        
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $totalOriginalSize += strlen($content);
            
            // Ajouter un commentaire pour identifier le fichier source
            $bundleContent .= "/* " . basename($file) . " */\n";
            $bundleContent .= $content . "\n\n";
        }
        
        // Minifier le bundle
        $minifiedBundle = $this->minifyCSS($bundleContent);
        
        // Sauvegarder
        $bundleFile = $this->outputDir . 'css/bundle.min.css';
        file_put_contents($bundleFile, $minifiedBundle);
        
        // Compression
        if ($this->enableCompression) {
            $gzipFile = $bundleFile . '.gz';
            file_put_contents($gzipFile, gzencode($minifiedBundle, 9));
        }
        
        return [
            'original_size' => $totalOriginalSize,
            'bundled_size' => strlen($minifiedBundle),
            'compression_ratio' => round((1 - strlen($minifiedBundle) / $totalOriginalSize) * 100, 2),
            'output_file' => $bundleFile,
            'gzip_file' => $this->enableCompression ? $gzipFile : null
        ];
    }
    
    /**
     * Optimiser les fichiers JavaScript
     */
    public function optimizeJS() {
        $jsFiles = glob($this->assetsDir . 'js/*.js');
        $results = [];
        
        foreach ($jsFiles as $file) {
            $result = $this->processJS($file);
            $results[basename($file)] = $result;
        }
        
        // Créer un bundle JS combiné
        $bundleResult = $this->createJSBundle($jsFiles);
        $results['bundle'] = $bundleResult;
        
        return $results;
    }
    
    /**
     * Traiter un fichier JavaScript
     */
    private function processJS($file) {
        $fileName = basename($file, '.js');
        $content = file_get_contents($file);
        $originalSize = strlen($content);
        
        // Vérifier le cache
        $cacheKey = md5($content);
        $cachedFile = $this->cacheDir . $fileName . '_' . $cacheKey . '.js';
        
        if (file_exists($cachedFile)) {
            $minifiedContent = file_get_contents($cachedFile);
        } else {
            // Minifier le JavaScript
            $minifiedContent = $this->minifyJS($content);
            
            // Sauvegarder dans le cache
            file_put_contents($cachedFile, $minifiedContent);
        }
        
        // Sauvegarder le fichier optimisé
        $outputFile = $this->outputDir . 'js/' . $fileName . '.min.js';
        file_put_contents($outputFile, $minifiedContent);
        
        // Compression gzip
        if ($this->enableCompression) {
            $gzipFile = $outputFile . '.gz';
            file_put_contents($gzipFile, gzencode($minifiedContent, 9));
        }
        
        return [
            'original_size' => $originalSize,
            'minified_size' => strlen($minifiedContent),
            'compression_ratio' => round((1 - strlen($minifiedContent) / $originalSize) * 100, 2),
            'output_file' => $outputFile,
            'gzip_file' => $this->enableCompression ? $gzipFile : null
        ];
    }
    
    /**
     * Minifier le JavaScript (version basique)
     */
    private function minifyJS($js) {
        // Supprimer les commentaires sur une ligne
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Supprimer les commentaires multi-lignes
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Supprimer les espaces multiples
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Supprimer les espaces autour des opérateurs
        $js = preg_replace('/\s*([=+\-*\/!<>{}();,:])\s*/', '$1', $js);
        
        // Supprimer les point-virgules avant les accolades fermantes
        $js = preg_replace('/;+}/', '}', $js);
        
        // Supprimer les espaces en début et fin
        $js = trim($js);
        
        return $js;
    }
    
    /**
     * Créer un bundle JavaScript
     */
    private function createJSBundle($jsFiles) {
        $bundleContent = '';
        $totalOriginalSize = 0;
        
        // Ordre de chargement des fichiers
        $loadOrder = ['cache.js', 'main.js', 'analytics.js'];
        $orderedFiles = [];
        
        // Trier les fichiers selon l'ordre de chargement
        foreach ($loadOrder as $fileName) {
            foreach ($jsFiles as $file) {
                if (basename($file) === $fileName) {
                    $orderedFiles[] = $file;
                    break;
                }
            }
        }
        
        // Ajouter les fichiers restants
        foreach ($jsFiles as $file) {
            if (!in_array($file, $orderedFiles)) {
                $orderedFiles[] = $file;
            }
        }
        
        foreach ($orderedFiles as $file) {
            $content = file_get_contents($file);
            $totalOriginalSize += strlen($content);
            
            // Ajouter un commentaire pour identifier le fichier source
            $bundleContent .= "/* " . basename($file) . " */\n";
            $bundleContent .= $content . "\n;\n"; // Ajouter un ; pour éviter les erreurs
        }
        
        // Minifier le bundle
        $minifiedBundle = $this->minifyJS($bundleContent);
        
        // Sauvegarder
        $bundleFile = $this->outputDir . 'js/bundle.min.js';
        file_put_contents($bundleFile, $minifiedBundle);
        
        // Compression
        if ($this->enableCompression) {
            $gzipFile = $bundleFile . '.gz';
            file_put_contents($gzipFile, gzencode($minifiedBundle, 9));
        }
        
        return [
            'original_size' => $totalOriginalSize,
            'bundled_size' => strlen($minifiedBundle),
            'compression_ratio' => round((1 - strlen($minifiedBundle) / $totalOriginalSize) * 100, 2),
            'output_file' => $bundleFile,
            'gzip_file' => $this->enableCompression ? $gzipFile : null
        ];
    }
    
    /**
     * Optimiser les images (version basique)
     */
    public function optimizeImages() {
        $imageFiles = glob($this->assetsDir . 'images/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $results = [];
        
        foreach ($imageFiles as $file) {
            $result = $this->processImage($file);
            $results[basename($file)] = $result;
        }
        
        return $results;
    }
    
    /**
     * Traiter une image
     */
    private function processImage($file) {
        $fileName = basename($file);
        $fileInfo = pathinfo($file);
        $originalSize = filesize($file);
        
        // Copier l'image dans le dossier de sortie
        $outputFile = $this->outputDir . 'images/' . $fileName;
        copy($file, $outputFile);
        
        // Optimisations basiques selon le type
        $optimizedSize = $originalSize;
        
        if (extension_loaded('gd')) {
            $optimizedSize = $this->optimizeImageWithGD($file, $outputFile);
        }
        
        return [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'compression_ratio' => round((1 - $optimizedSize / $originalSize) * 100, 2),
            'output_file' => $outputFile
        ];
    }
    
    /**
     * Optimiser une image avec GD
     */
    private function optimizeImageWithGD($inputFile, $outputFile) {
        $imageInfo = getimagesize($inputFile);
        
        if (!$imageInfo) {
            return filesize($inputFile);
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Créer l'image source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($inputFile);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($inputFile);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($inputFile);
                break;
            default:
                return filesize($inputFile);
        }
        
        if (!$source) {
            return filesize($inputFile);
        }
        
        // Créer l'image de destination
        $destination = imagecreatetruecolor($width, $height);
        
        // Préserver la transparence pour PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
            imagefill($destination, 0, 0, $transparent);
        }
        
        // Copier l'image
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $width, $height);
        
        // Sauvegarder avec optimisation
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $outputFile, 85); // Qualité 85%
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $outputFile, 6); // Compression niveau 6
                break;
            case IMAGETYPE_GIF:
                imagegif($destination, $outputFile);
                break;
        }
        
        // Libérer la mémoire
        imagedestroy($source);
        imagedestroy($destination);
        
        return filesize($outputFile);
    }
    
    /**
     * Générer un manifeste des assets
     */
    public function generateManifest() {
        $manifest = [
            'generated' => date('c'),
            'version' => time(),
            'files' => []
        ];
        
        // Scanner les fichiers optimisés
        $distFiles = glob($this->outputDir . '{css,js,images}/*.{css,js,png,jpg,jpeg,gif,webp}', GLOB_BRACE);
        
        foreach ($distFiles as $file) {
            $relativePath = str_replace($this->outputDir, '', $file);
            $hash = md5_file($file);
            $size = filesize($file);
            
            $manifest['files'][$relativePath] = [
                'hash' => $hash,
                'size' => $size,
                'mtime' => filemtime($file)
            ];
        }
        
        // Sauvegarder le manifeste
        $manifestFile = $this->outputDir . 'manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifest;
    }
    
    /**
     * Nettoyer les fichiers de cache anciens
     */
    public function cleanCache() {
        $cacheFiles = glob($this->cacheDir . '*');
        $maxAge = 7 * 24 * 60 * 60; // 7 jours
        $now = time();
        $cleaned = 0;
        
        foreach ($cacheFiles as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Obtenir les statistiques d'optimisation
     */
    public function getStats() {
        $cssFiles = glob($this->outputDir . 'css/*.css');
        $jsFiles = glob($this->outputDir . 'js/*.js');
        $imageFiles = glob($this->outputDir . 'images/*');
        
        $stats = [
            'css' => [
                'files' => count($cssFiles),
                'total_size' => 0
            ],
            'js' => [
                'files' => count($jsFiles),
                'total_size' => 0
            ],
            'images' => [
                'files' => count($imageFiles),
                'total_size' => 0
            ]
        ];
        
        foreach ($cssFiles as $file) {
            $stats['css']['total_size'] += filesize($file);
        }
        
        foreach ($jsFiles as $file) {
            $stats['js']['total_size'] += filesize($file);
        }
        
        foreach ($imageFiles as $file) {
            $stats['images']['total_size'] += filesize($file);
        }
        
        $stats['total_size'] = $stats['css']['total_size'] + $stats['js']['total_size'] + $stats['images']['total_size'];
        
        return $stats;
    }
}

?>