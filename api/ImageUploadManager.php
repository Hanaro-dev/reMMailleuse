<?php
/**
 * Gestionnaire d'upload d'images avec compression et optimisation
 * Support JPEG, PNG, WebP avec conversion automatique
 */

class ImageUploadManager {
    private $config;
    private $uploadDir;
    private $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    
    public function __construct($config) {
        $this->config = $config;
        $this->uploadDir = $config['upload']['upload_dir'];
        $this->ensureUploadDir();
    }
    
    /**
     * Traite un upload d'images multiples
     */
    public function handleMultipleUpload($files, $maxFiles = 5) {
        $uploadedFiles = [];
        
        if (empty($files['name'][0])) {
            return $uploadedFiles;
        }
        
        $fileCount = count($files['name']);
        
        if ($fileCount > $maxFiles) {
            throw new Exception("Trop de fichiers (maximum {$maxFiles})");
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileData = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'error' => $files['error'][$i],
                'type' => $files['type'][$i]
            ];
            
            $uploadedFiles[] = $this->processImage($fileData);
        }
        
        return $uploadedFiles;
    }
    
    /**
     * Traite une image unique
     */
    public function processSingleImage($fileData) {
        return $this->processImage($fileData);
    }
    
    /**
     * Traite une image avec compression et optimisation
     */
    private function processImage($fileData) {
        // Validations de base
        $this->validateFile($fileData);
        
        // Détection du type MIME réel
        $mimeType = $this->detectMimeType($fileData['tmp_name']);
        
        if (!isset($this->allowedTypes[$mimeType])) {
            throw new Exception("Type de fichier non autorisé: {$fileData['name']}");
        }
        
        // Générer les noms de fichiers
        $originalName = $fileData['name'];
        $extension = $this->allowedTypes[$mimeType];
        $baseName = $this->generateSafeName($originalName, $extension);
        
        // Créer les versions de l'image
        $versions = $this->createImageVersions($fileData['tmp_name'], $mimeType, $baseName);
        
        return [
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'original_size' => $fileData['size'],
            'versions' => $versions,
            'upload_time' => time()
        ];
    }
    
    /**
     * Crée différentes versions d'une image
     */
    private function createImageVersions($tmpPath, $mimeType, $baseName) {
        $versions = [];
        
        // Charger l'image source
        $sourceImage = $this->createImageFromFile($tmpPath, $mimeType);
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        // Configurations des versions
        $configs = [
            'original' => [
                'width' => $originalWidth,
                'height' => $originalHeight,
                'quality' => 85,
                'suffix' => ''
            ],
            'medium' => [
                'width' => 800,
                'height' => 600,
                'quality' => 80,
                'suffix' => '_medium'
            ],
            'thumb' => [
                'width' => 300,
                'height' => 200,
                'quality' => 75,
                'suffix' => '_thumb'
            ]
        ];
        
        foreach ($configs as $version => $config) {
            // Calculer les dimensions finales
            $dimensions = $this->calculateDimensions(
                $originalWidth, 
                $originalHeight, 
                $config['width'], 
                $config['height']
            );
            
            // Créer l'image redimensionnée
            $resizedImage = $this->resizeImage(
                $sourceImage, 
                $dimensions['width'], 
                $dimensions['height']
            );
            
            // Sauvegarder en différents formats
            $savedFiles = $this->saveImageVersions($resizedImage, $baseName, $config);
            
            $versions[$version] = [
                'dimensions' => $dimensions,
                'files' => $savedFiles
            ];
            
            imagedestroy($resizedImage);
        }
        
        imagedestroy($sourceImage);
        return $versions;
    }
    
    /**
     * Sauvegarde une image en différents formats
     */
    private function saveImageVersions($image, $baseName, $config) {
        $files = [];
        $suffix = $config['suffix'];
        $quality = $config['quality'];
        
        // Sauvegarder en WebP (format moderne)
        $webpPath = $this->uploadDir . $baseName . $suffix . '.webp';
        if (function_exists('imagewebp')) {
            imagewebp($image, $webpPath, $quality);
            $files['webp'] = [
                'path' => $webpPath,
                'size' => filesize($webpPath),
                'url' => $this->getPublicUrl($webpPath)
            ];
        }
        
        // Sauvegarder en JPEG (compatibilité)
        $jpegPath = $this->uploadDir . $baseName . $suffix . '.jpg';
        imagejpeg($image, $jpegPath, $quality);
        $files['jpeg'] = [
            'path' => $jpegPath,
            'size' => filesize($jpegPath),
            'url' => $this->getPublicUrl($jpegPath)
        ];
        
        return $files;
    }
    
    /**
     * Crée une image GD depuis un fichier
     */
    private function createImageFromFile($path, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            default:
                throw new Exception("Type d'image non supporté: {$mimeType}");
        }
    }
    
    /**
     * Redimensionne une image
     */
    private function resizeImage($sourceImage, $newWidth, $newHeight) {
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Préserver la transparence pour PNG
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        
        // Redimensionner avec antialiasing
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            imagesx($sourceImage), imagesy($sourceImage)
        );
        
        return $resizedImage;
    }
    
    /**
     * Calcule les dimensions en préservant le ratio
     */
    private function calculateDimensions($originalWidth, $originalHeight, $maxWidth, $maxHeight) {
        // Si l'image est plus petite que la taille max, garder la taille originale
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            return [
                'width' => $originalWidth,
                'height' => $originalHeight
            ];
        }
        
        // Calculer le ratio pour préserver les proportions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        return [
            'width' => round($originalWidth * $ratio),
            'height' => round($originalHeight * $ratio)
        ];
    }
    
    /**
     * Valide un fichier uploadé
     */
    private function validateFile($fileData) {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur upload fichier: {$fileData['name']}");
        }
        
        if ($fileData['size'] > $this->config['upload']['max_size']) {
            $maxSizeMB = round($this->config['upload']['max_size'] / (1024 * 1024));
            throw new Exception("Fichier trop volumineux (max {$maxSizeMB}MB): {$fileData['name']}");
        }
        
        if (!is_uploaded_file($fileData['tmp_name'])) {
            throw new Exception("Fichier non valide: {$fileData['name']}");
        }
    }
    
    /**
     * Détecte le type MIME réel d'un fichier
     */
    private function detectMimeType($filePath) {
        if (!function_exists('finfo_open')) {
            throw new Exception("Extension fileinfo non disponible");
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        return $mimeType;
    }
    
    /**
     * Génère un nom de fichier sécurisé
     */
    private function generateSafeName($originalName, $extension) {
        $timestamp = date('Y-m-d_H-i-s');
        $randomId = uniqid();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
        $safeName = substr($safeName, 0, 50); // Limiter la longueur
        
        return "{$timestamp}_{$randomId}_{$safeName}";
    }
    
    /**
     * Assure l'existence du dossier d'upload
     */
    private function ensureUploadDir() {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier d'upload");
            }
        }
        
        // Créer un fichier .htaccess pour la sécurité
        $htaccessPath = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Sécurité uploads\n";
            $htaccessContent .= "Options -Indexes\n";
            $htaccessContent .= "Options -ExecCGI\n";
            $htaccessContent .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)\$\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</FilesMatch>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
    
    /**
     * Génère l'URL publique d'un fichier
     */
    private function getPublicUrl($filePath) {
        return str_replace(
            dirname(__DIR__), 
            '', 
            $filePath
        );
    }
    
    /**
     * Supprime les fichiers d'upload
     */
    public function deleteUploadedFiles($versions) {
        foreach ($versions as $version) {
            foreach ($version['files'] as $file) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                }
            }
        }
    }
    
    /**
     * Obtient les informations d'une image
     */
    public function getImageInfo($filePath) {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $info = getimagesize($filePath);
        if (!$info) {
            return null;
        }
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime_type' => $info['mime'],
            'size' => filesize($filePath)
        ];
    }
}

?>