# Système d'Upload d'Images avec Compression - ReMmailleuse

## Vue d'ensemble

Le système d'upload d'images a été complètement refait avec compression automatique, génération de multiples versions et optimisation des performances.

## Fonctionnalités

### 🎯 Compression et optimisation
- **Compression automatique** avec différents niveaux de qualité
- **Génération de 3 versions** : originale, moyenne, miniature
- **Support WebP** pour les navigateurs modernes
- **Redimensionnement intelligent** avec préservation du ratio
- **Optimisation de taille** jusqu'à 80% de réduction

### 🔒 Sécurité renforcée
- **Validation MIME** stricte côté serveur
- **Protection contre les scripts** dans les uploads
- **Dossier sécurisé** avec .htaccess
- **Tokens CSRF** pour toutes les requêtes
- **Nettoyage automatique** des anciens fichiers

### 🎨 Interface moderne
- **Drag & Drop** intuitif
- **Prévisualisation** en temps réel
- **Feedback visuel** pour chaque étape
- **Responsive design** pour mobile
- **Animations fluides**

## Architecture

### Structure des fichiers

```
api/
├── ImageUploadManager.php    # Classe de gestion d'upload
├── upload.php               # API endpoint pour upload
└── contact.php              # API modifiée pour utiliser le nouveau système

assets/js/
└── image-upload.js          # Composant JavaScript d'upload

uploads/
├── .htaccess               # Sécurité globale
├── images/                 # Uploads via API upload.php
└── contact/                # Uploads via formulaire contact
```

## Classe ImageUploadManager

### Fonctionnalités principales

```php
class ImageUploadManager {
    // Traitement d'uploads multiples
    public function handleMultipleUpload($files, $maxFiles = 5)
    
    // Traitement d'une image unique
    public function processSingleImage($fileData)
    
    // Création de versions d'images
    private function createImageVersions($tmpPath, $mimeType, $baseName)
    
    // Sauvegarde en multiple formats
    private function saveImageVersions($image, $baseName, $config)
    
    // Utilitaires
    public function getImageInfo($filePath)
    public function deleteUploadedFiles($versions)
}
```

### Versions générées

Pour chaque image uploadée :

1. **Version originale** (qualité 85%)
   - Dimensions préservées
   - Compression légère
   - Formats : JPEG + WebP

2. **Version moyenne** (qualité 80%)
   - Max 800x600px
   - Compression optimisée
   - Formats : JPEG + WebP

3. **Version miniature** (qualité 75%)
   - Max 300x200px
   - Compression élevée
   - Formats : JPEG + WebP

## API Endpoints

### POST /api/upload.php

**Paramètres :**
- `csrf_token` : Token CSRF (requis)
- `images[]` : Array de fichiers image

**Réponse :**
```json
{
    "success": true,
    "message": "Images uploadées avec succès",
    "count": 2,
    "images": [
        {
            "original_name": "photo1.jpg",
            "mime_type": "image/jpeg",
            "original_size": 2048576,
            "compressed_size": 512000,
            "compression_ratio": 75.0,
            "urls": {
                "original": "/uploads/images/2025-01-15_123456_abc123.jpg",
                "medium": "/uploads/images/2025-01-15_123456_abc123_medium.jpg",
                "thumb": "/uploads/images/2025-01-15_123456_abc123_thumb.jpg",
                "webp": {
                    "original": "/uploads/images/2025-01-15_123456_abc123.webp",
                    "medium": "/uploads/images/2025-01-15_123456_abc123_medium.webp",
                    "thumb": "/uploads/images/2025-01-15_123456_abc123_thumb.webp"
                }
            },
            "dimensions": {
                "original": {"width": 1920, "height": 1080},
                "medium": {"width": 800, "height": 450},
                "thumb": {"width": 300, "height": 169}
            }
        }
    ]
}
```

### Intégration dans contact.php

L'API de contact utilise maintenant le nouveau système :

```php
// Traitement des uploads avec compression
$uploadedFiles = handleFileUploads();

// Résultat enrichi avec informations de compression
foreach ($uploadedFiles as $file) {
    // $file['compression_ratio'] : pourcentage de compression
    // $file['versions'] : URLs des différentes versions
    // $file['webp_versions'] : URLs des versions WebP
}
```

## Composant JavaScript

### Utilisation basique

```html
<div id="upload-container"></div>

<script>
const uploader = new ImageUploadManager('upload-container', {
    maxFiles: 5,
    maxSize: 10 * 1024 * 1024, // 10MB
    uploadUrl: '/api/upload.php',
    onUploadSuccess: (result) => {
        console.log('Upload réussi:', result);
    },
    onUploadError: (error) => {
        console.error('Erreur upload:', error);
    }
});

// Upload programmatique
uploader.uploadFiles().then(result => {
    if (result) {
        console.log('Images uploadées:', result.images);
    }
});
</script>
```

### Options disponibles

```javascript
const options = {
    maxFiles: 5,                    // Nombre max de fichiers
    maxSize: 10 * 1024 * 1024,     // Taille max par fichier
    allowedTypes: [...],           // Types MIME autorisés
    uploadUrl: '/api/upload.php',  // URL d'upload
    previewContainer: 'id',        // Container de prévisualisation
    onUploadStart: callback,       // Début d'upload
    onUploadProgress: callback,    // Progression (si supporté)
    onUploadSuccess: callback,     // Succès
    onUploadError: callback        // Erreur
};
```

### Méthodes disponibles

```javascript
// Gestion des fichiers
uploader.handleFiles(files)        // Ajouter des fichiers
uploader.removeFile(file, id)      // Supprimer un fichier
uploader.clear()                   // Vider la sélection

// Upload
uploader.uploadFiles()             // Uploader tous les fichiers
uploader.isUploading()             // Vérifier l'état

// Utilitaires
uploader.getFiles()                // Obtenir la liste des fichiers
uploader.formatFileSize(bytes)     // Formater taille
uploader.validateFile(file)        // Valider un fichier
```

## Sécurité

### Côté serveur

1. **Validation MIME stricte**
   ```php
   $mimeType = $this->detectMimeType($fileData['tmp_name']);
   if (!isset($this->allowedTypes[$mimeType])) {
       throw new Exception("Type non autorisé");
   }
   ```

2. **Protection uploads**
   ```apache
   # .htaccess dans /uploads/
   Options -Indexes
   Options -ExecCGI
   <FilesMatch "\.(php|php3|php4|php5|phtml)$">
       Require all denied
   </FilesMatch>
   ```

3. **Noms de fichiers sécurisés**
   ```php
   $safeName = date('Y-m-d_H-i-s') . '_' . uniqid() . '_' . $cleanName;
   ```

### Côté client

1. **Validation avant upload**
   - Vérification du type MIME
   - Contrôle de la taille
   - Limite du nombre de fichiers

2. **Protection CSRF**
   - Token automatique dans toutes les requêtes
   - Validation côté serveur

## Configuration

### Paramètres serveur

```php
// Dans ImageUploadManager
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
```

### Paramètres client

```javascript
// Dans image-upload.js
const defaultOptions = {
    maxFiles: 5,
    maxSize: 10 * 1024 * 1024,
    allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    uploadUrl: '/api/upload.php'
};
```

## Optimisations

### Performance

1. **Compression efficace**
   - Algorithmes optimisés
   - Qualité adaptative
   - Préservation des métadonnées importantes

2. **Redimensionnement intelligent**
   - Ratio préservé
   - Antialiasing activé
   - Optimisation mémoire

3. **Formats modernes**
   - WebP pour les navigateurs compatibles
   - JPEG fallback pour la compatibilité
   - Choix automatique du meilleur format

### Espace disque

1. **Nettoyage automatique**
   - Suppression des fichiers > 30 jours
   - Probabilité de nettoyage : 1% par requête
   - Logs des opérations

2. **Gestion des versions**
   - Suppression en cascade
   - Optimisation des tailles
   - Déduplication (future amélioration)

## Monitoring

### Métriques disponibles

- Nombre d'uploads par jour
- Taille totale des fichiers
- Taux de compression moyen
- Erreurs d'upload
- Utilisation de l'espace disque

### Logs

```php
// Logs d'upload réussi
error_log("Upload réussi: " . count($images) . " fichiers");

// Logs d'erreur
error_log("Erreur upload: " . $e->getMessage());
```

## Maintenance

### Nettoyage manuel

```php
// Nettoyer les anciens uploads
function cleanupOldUploads($directory, $maxAge = 2592000) {
    // $maxAge = 30 jours en secondes
    $files = glob($directory . '*');
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
            unlink($file);
        }
    }
}
```

### Vérification de l'espace disque

```bash
# Vérifier l'utilisation du dossier uploads
du -sh uploads/

# Nettoyer les fichiers > 30 jours
find uploads/ -type f -mtime +30 -delete
```

## Compatibilité

### Navigateurs supportés

- **Chrome/Edge** 60+ (full support)
- **Firefox** 55+ (full support)
- **Safari** 12+ (full support)
- **Mobile browsers** (iOS 12+, Android 7+)

### Fallbacks

- **JavaScript désactivé** : formulaire HTML basique
- **Drag & Drop non supporté** : clic pour sélectionner
- **WebP non supporté** : fallback JPEG automatique

## Dépannage

### Erreurs communes

1. **"Extension fileinfo non disponible"**
   - Vérifier que l'extension PHP fileinfo est activée
   - `php -m | grep fileinfo`

2. **"Impossible de créer le dossier d'upload"**
   - Vérifier les permissions (755)
   - Vérifier l'espace disque disponible

3. **"Fichier trop volumineux"**
   - Ajuster `upload_max_filesize` dans php.ini
   - Ajuster `post_max_size` dans php.ini
   - Vérifier la limite dans le code

4. **"Token CSRF invalide"**
   - Vérifier que csrf.js est chargé
   - Vérifier la configuration des sessions

### Debug

```php
// Activer les logs détaillés
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier les extensions
var_dump(extension_loaded('gd'));
var_dump(extension_loaded('fileinfo'));
```

---
*Système d'upload d'images implémenté le 2025-07-15*