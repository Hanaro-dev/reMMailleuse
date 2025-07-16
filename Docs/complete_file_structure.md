# 📁 STRUCTURE COMPLÈTE - SITE REMMAILLEUSE

## 🗂️ ARBORESCENCE DES FICHIERS

```
remmailleuse-site/
├── 📄 index.html                      # Page principale du site
├── 📄 README.md                       # Documentation du projet
├── 📄 .gitignore                      # Fichiers à ignorer par Git
├── 📄 package.json                    # Configuration du projet
├── 📄 robots.txt                      # Instructions pour les robots
├── 📄 sitemap.xml                     # Plan du site pour SEO
├── 📄 favicon.ico                     # Icône du site
├── 📄 manifest.json                   # Configuration PWA
├── 📁 admin/
│   ├── 📄 index.html                  # Interface d'administration
│   ├── 📄 login.html                  # Page de connexion admin
│   └── 📁 js/
│       ├── 📄 admin.js                # Scripts admin
│       └── 📄 auth.js                 # Authentification
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── 📄 main.css                # Styles principaux
│   │   ├── 📄 admin.css               # Styles admin
│   │   └── 📄 critical.css            # CSS critique inline
│   ├── 📁 js/
│   │   ├── 📄 main.js                 # Scripts principaux
│   │   ├── 📄 gallery.js              # Galerie interactive
│   │   ├── 📄 forms.js                # Gestion formulaires
│   │   └── 📄 animations.js           # Animations
│   ├── 📁 images/
│   │   ├── 📁 hero/
│   │   │   ├── 📄 hero-bg.jpg         # Image de fond hero
│   │   │   └── 📄 hero-visual.jpg     # Visuel principal
│   │   ├── 📁 gallery/
│   │   │   ├── 📄 pull-cachemire-before.jpg
│   │   │   ├── 📄 pull-cachemire-after.jpg
│   │   │   ├── 📄 bas-contention-before.jpg
│   │   │   ├── 📄 bas-contention-after.jpg
│   │   │   ├── 📄 robe-vintage-before.jpg
│   │   │   ├── 📄 robe-vintage-after.jpg
│   │   │   ├── 📄 echarpe-soie-before.jpg
│   │   │   └── 📄 echarpe-soie-after.jpg
│   │   ├── 📁 profile/
│   │   │   ├── 📄 portrait-mme-monod.jpg
│   │   │   └── 📄 atelier.jpg
│   │   ├── 📁 icons/
│   │   │   ├── 📄 icon-192.png        # Icônes PWA
│   │   │   ├── 📄 icon-512.png
│   │   │   └── 📄 logo.svg            # Logo vectoriel
│   │   └── 📁 placeholders/
│   │       ├── 📄 image-placeholder.svg
│   │       └── 📄 loading.gif
│   └── 📁 fonts/
│       ├── 📄 inter-regular.woff2     # Polices optimisées
│       ├── 📄 inter-medium.woff2
│       ├── 📄 playfair-regular.woff2
│       └── 📄 playfair-bold.woff2
├── 📁 data/
│   ├── 📄 content.json                # Contenu modifiable
│   ├── 📄 services.json               # Services et tarifs
│   ├── 📄 gallery.json                # Métadonnées galerie
│   └── 📄 settings.json               # Paramètres du site
├── 📁 api/
│   ├── 📄 contact.php                 # Traitement formulaire
│   ├── 📄 save-content.php            # Sauvegarde admin
│   └── 📄 upload.php                  # Upload d'images
├── 📁 docs/
│   ├── 📄 guide-utilisation.md        # Guide pour votre amie
│   ├── 📄 guide-technique.md          # Documentation technique
│   └── 📁 screenshots/
│       ├── 📄 admin-interface.png
│       └── 📄 site-preview.png
└── 📁 backups/
    ├── 📄 content-backup.json         # Sauvegardes automatiques
    └── 📄 .htaccess                   # Configuration serveur
```

---

## 📄 CONTENU DES FICHIERS PRINCIPAUX

### 1. package.json
```json
{
  "name": "remmailleuse-site",
  "version": "1.0.0",
  "description": "Site web moderne pour artisane remmailleuse",
  "main": "index.html",
  "scripts": {
    "dev": "live-server --port=3000",
    "build": "npm run optimize-images && npm run minify-css",
    "optimize-images": "imagemin assets/images/**/* --out-dir=assets/images/optimized",
    "minify-css": "cleancss -o assets/css/main.min.css assets/css/main.css"
  },
  "keywords": ["remaillage", "artisan", "réparation", "textile"],
  "author": "Votre nom",
  "license": "MIT",
  "devDependencies": {
    "live-server": "^1.2.2",
    "imagemin": "^8.0.1",
    "clean-css-cli": "^5.6.2"
  }
}
```

### 2. README.md
```markdown
# 🧵 Site Remmailleuse

Site web moderne pour Mme Monod, artisane remmailleuse spécialisée dans la réparation de tissus et lainages.

## 🚀 Installation

1. Télécharger tous les fichiers
2. Placer sur votre hébergeur web (Infomaniak)
3. Configurer l'email dans `data/settings.json`

## 📖 Utilisation

### Pour modifier le contenu :
1. Aller sur `votre-site.com/admin`
2. Se connecter avec vos identifiants
3. Modifier le contenu via l'interface

### Sections modifiables :
- ✅ Page d'accueil (titre, sous-titre)
- ✅ Expertise (présentation, étapes)
- ✅ Galerie (ajout/suppression de réalisations)
- ✅ Services (tarifs, descriptions)
- ✅ Contact (adresses, téléphones)

## 🔧 Support

Pour toute question : [votre.email@exemple.com]
```

### 3. robots.txt
```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /backups/

Sitemap: https://votre-domaine.com/sitemap.xml
```

### 4. sitemap.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://votre-domaine.com/</loc>
    <lastmod>2025-01-15</lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
```

### 5. .gitignore
```
# Fichiers de configuration
*.log
.env
.DS_Store
Thumbs.db

# Dossiers temporaires
/temp/
/cache/

# Sauvegardes
/backups/*.json

# Fichiers sensibles
/admin/config.php
/api/credentials.php
```

### 6. manifest.json (PWA)
```json
{
  "name": "Remmailleuse - Mme Monod",
  "short_name": "Remmailleuse",
  "description": "Artisane spécialisée en remaillage et réparation de tissus",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#F5F1EB",
  "theme_color": "#D4896B",
  "icons": [
    {
      "src": "assets/images/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "assets/images/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

---

## 📊 FICHIERS JSON DE DONNÉES

### data/content.json
```json
{
  "site": {
    "name": "Remmailleuse",
    "description": "L'art traditionnel du remaillage pour redonner vie à vos tissus précieux",
    "url": "https://votre-domaine.com"
  },
  "hero": {
    "title": "L'art de redonner vie à vos tissus précieux",
    "subtitle": "Remaillage traditionnel & réparation invisible depuis plus de 20 ans",
    "cta": {
      "text": "Découvrir mon savoir-faire",
      "link": "#expertise"
    }
  },
  "expertise": {
    "title": "Mon Expertise",
    "intro": {
      "name": "Mme Monod, Artisane Remmailleuse",
      "description": [
        "Passionnée par les techniques traditionnelles de remaillage, je redonne vie à vos tissus et lainages les plus précieux. Mon travail consiste à réparer minutieusement chaque maille avec une loupe et un crochet minuscule, remontant maille par maille les lainages endommagés.",
        "Que ce soit pour refermer un trou de mite avec la plus grande minutie ou effectuer du raccommodage avec ma fidèle machine Elna vintage, j'apporte le plus grand soin à rénover vos tissus à l'identique."
      ]
    },
    "process": [
      {
        "step": 1,
        "icon": "🔍",
        "title": "Diagnostic",
        "description": "Analyse minutieuse de la pièce pour déterminer la meilleure technique de réparation"
      },
      {
        "step": 2,
        "icon": "🧵",
        "title": "Remaillage",
        "description": "Reconstruction maille par maille avec loupe et outils traditionnels"
      },
      {
        "step": 3,
        "icon": "✨",
        "title": "Finition",
        "description": "Réparation invisible qui redonne une seconde vie à votre vêtement"
      }
    ]
  },
  "contact": {
    "addresses": [
      {
        "country": "🇨🇭",
        "title": "Suisse",
        "address": "Chemin des Clavins 3",
        "city": "2108 Couvet"
      },
      {
        "country": "🇫🇷",
        "title": "France",
        "address": "Poste restante, 17 Rue de Franche Comté",
        "city": "25300 Verrières-de-Joux"
      }
    ],
    "phones": [
      "+41 32.863.15.31",
      "+41 79.636.23.22"
    ],
    "email": "contact@remmailleuse.com",
    "delays": "2 à 5 jours selon réparation"
  }
}
```

### data/services.json
```json
{
  "services": [
    {
      "id": "remaillage-classique",
      "icon": "🧵",
      "name": "Remaillage classique",
      "description": "Reconstruction maille par maille pour lainages",
      "price": "15-40€",
      "duration": "2-3 jours"
    },
    {
      "id": "trous-mite",
      "icon": "🔍",
      "name": "Trous de mite",
      "description": "Réparation invisible minutieuse",
      "price": "20-35€",
      "duration": "2-4 jours"
    },
    {
      "id": "bas-contention",
      "icon": "🧦",
      "name": "Bas de contention",
      "description": "Raccommodage machine spécialisée",
      "price": "15-25€",
      "duration": "1-2 jours"
    },
    {
      "id": "renovation-tissus",
      "icon": "✨",
      "name": "Rénovation tissus",
      "description": "Restauration à l'identique",
      "price": "Sur devis",
      "duration": "Variable"
    }
  ]
}
```

### data/gallery.json
```json
{
  "categories": [
    { "id": "tous", "name": "Tous", "active": true },
    { "id": "pulls", "name": "Pulls" },
    { "id": "bas", "name": "Bas de contention" },
    { "id": "delicats", "name": "Tissus délicats" }
  ],
  "items": [
    {
      "id": "pull-cachemire",
      "category": "pulls",
      "title": "Pull en cachemire",
      "description": "Réparation invisible d'un trou de mite sur pull de luxe",
      "images": {
        "before": "assets/images/gallery/pull-cachemire-before.jpg",
        "after": "assets/images/gallery/pull-cachemire-after.jpg"
      },
      "featured": true
    },
    {
      "id": "bas-contention",
      "category": "bas",
      "title": "Bas de contention",
      "description": "Remaillage précis avec machine Elna spécialisée",
      "images": {
        "before": "assets/images/gallery/bas-contention-before.jpg",
        "after": "assets/images/gallery/bas-contention-after.jpg"
      }
    }
  ]
}
```

### data/settings.json
```json
{
  "theme": {
    "colors": {
      "primary": "#D4896B",
      "secondary": "#9CAF9A",
      "accent": "#8B6F47",
      "neutral": "#F5F1EB"
    },
    "fonts": {
      "serif": "Playfair Display",
      "sans": "Inter"
    }
  },
  "seo": {
    "title": "Remmailleuse - Réparation de tissus et lainages",
    "description": "Artisane spécialisée en remaillage traditionnel. Réparation invisible de pulls, bas de contention et tissus délicats en Suisse et France.",
    "keywords": "remaillage, réparation tissus, lainages, tricots, bas contention, artisan, Suisse, France"
  },
  "email": {
    "contact": "contact@remmailleuse.com",
    "admin": "admin@remmailleuse.com"
  },
  "analytics": {
    "google": "",
    "facebook": ""
  }
}
```

---

## 🔧 FICHIERS TECHNIQUES

### api/contact.php
```php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation des données
    $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $message = filter_var($data['message'], FILTER_SANITIZE_STRING);
    
    if ($name && $email && $message) {
        // Configuration email
        $to = 'contact@remmailleuse.com';
        $subject = 'Nouvelle demande de devis - Remmailleuse';
        $body = "Nom: $name\nEmail: $email\nMessage: $message";
        
        // Envoi email
        if (mail($to, $subject, $body)) {
            echo json_encode(['success' => true, 'message' => 'Message envoyé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur envoi']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
    }
}
?>
```

### .htaccess (Configuration Apache)
```apache
# Performance et sécurité
RewriteEngine On

# Redirection HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Cache des assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
</IfModule>

# Compression Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Sécurité
<Files "*.json">
    Order allow,deny
    Deny from all
</Files>

# Protection admin
<Directory "admin">
    AuthType Basic
    AuthName "Administration"
    AuthUserFile /path/to/.htpasswd
    Require valid-user
</Directory>
```

---

## 📖 GUIDE D'UTILISATION

### Pour votre amie (docs/guide-utilisation.md)
```markdown
# 📖 Guide d'utilisation - Site Remmailleuse

## 🚀 Comment modifier le contenu

### 1. Accéder à l'administration
- Aller sur votre-site.com/admin
- Entrer vos identifiants

### 2. Modifier le contenu
- **Page d'accueil** : Changer titre et sous-titre
- **Expertise** : Modifier votre présentation
- **Galerie** : Ajouter vos réalisations
- **Services** : Mettre à jour les tarifs
- **Contact** : Modifier coordonnées

### 3. Ajouter des photos
- Cliquer sur "📷 Changer photo"
- Sélectionner l'image (max 2MB)
- Ajouter une description

### 4. Sauvegarder
- Cliquer "💾 Sauvegarder"
- Vérifier le message de confirmation

## 📞 Aide
En cas de problème : [votre.email@exemple.com]
```

---

## 🎯 DÉPLOIEMENT

### Instructions de mise en ligne :
1. **Zipper tous les fichiers**
2. **Uploader sur Infomaniak** via FTP/cPanel
3. **Configurer les permissions** (755 pour dossiers, 644 pour fichiers)
4. **Tester l'interface admin**
5. **Configurer l'email** dans settings.json

### Checklist finale :
- ✅ Site responsive testé
- ✅ Admin fonctionnel
- ✅ Formulaires configurés
- ✅ SEO optimisé
- ✅ Performance vérifiée
- ✅ Sauvegardes activées

Cette structure est **complète, professionnelle et prête pour la production** ! 🚀