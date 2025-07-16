# 🏗️ STRUCTURE TECHNIQUE COMPLÈTE - SITE REMMAILLEUSE

## 📁 ARBORESCENCE DES FICHIERS

```
remmailleuse-site/
├── 📄 index.html                    # Page principale
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── main.css                 # Styles principaux
│   │   ├── components.css           # Composants réutilisables
│   │   └── animations.css           # Animations CSS
│   ├── 📁 js/
│   │   ├── main.js                  # Script principal
│   │   ├── gallery.js               # Galerie interactive
│   │   ├── forms.js                 # Gestion formulaires
│   │   └── animations.js            # Animations JS
│   ├── 📁 images/
│   │   ├── hero/                    # Images hero section
│   │   ├── gallery/                 # Galerie avant/après
│   │   ├── portrait/                # Photo de l'artisane
│   │   └── icons/                   # Icônes et logos
│   └── 📁 fonts/                    # Polices personnalisées
├── 📁 data/
│   ├── content.json                 # Contenu modifiable
│   ├── services.json                # Services et tarifs
│   └── testimonials.json            # Témoignages clients
├── 📁 admin/
│   ├── edit.html                    # Interface d'édition simple
│   └── admin.js                     # Scripts d'administration
├── 📄 sitemap.xml                   # Plan du site (SEO)
├── 📄 robots.txt                    # Instructions robots
└── 📄 README.md                     # Documentation
```

---

## 🎨 STRUCTURE HTML SÉMANTIQUE

### Page Unique (SPA) avec Sections

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Meta SEO optimisés -->
    <!-- Préchargement des polices -->
    <!-- CSS critiques inline -->
</head>
<body>
    <!-- Navigation fixe -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Remmailleuse</div>
            <ul class="nav-menu">
                <li><a href="#expertise">Expertise</a></li>
                <li><a href="#galerie">Galerie</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <button class="theme-toggle">🌙</button>
        </div>
    </nav>

    <!-- Section Hero -->
    <section id="hero" class="hero">
        <div class="hero-content">
            <h1>L'art de redonner vie à vos tissus précieux</h1>
            <p>Remaillage traditionnel & réparation invisible</p>
            <button class="cta-button">Découvrir mon savoir-faire</button>
        </div>
        <div class="hero-visual">
            <!-- Animation de fils qui se tissent -->
        </div>
    </section>

    <!-- Section Expertise -->
    <section id="expertise" class="expertise">
        <div class="container">
            <div class="expertise-intro">
                <img src="assets/images/portrait/artisane.jpg" alt="Portrait">
                <div class="intro-text">
                    <!-- Contenu chargé depuis content.json -->
                </div>
            </div>
            <div class="process-steps">
                <!-- 3 étapes illustrées -->
            </div>
        </div>
    </section>

    <!-- Section Galerie -->
    <section id="galerie" class="gallery">
        <div class="container">
            <h2>Mes réalisations</h2>
            <div class="gallery-filters">
                <!-- Filtres par catégorie -->
            </div>
            <div class="gallery-grid">
                <!-- Images avant/après chargées dynamiquement -->
            </div>
            <div class="gallery-modal">
                <!-- Modal pour zoom -->
            </div>
        </div>
    </section>

    <!-- Section Services -->
    <section id="services" class="services">
        <div class="container">
            <h2>Services & Tarifs</h2>
            <div class="services-grid">
                <!-- Cards de services chargées depuis services.json -->
            </div>
            <div class="price-calculator">
                <!-- Simulateur de prix -->
            </div>
        </div>
    </section>

    <!-- Section Contact -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-info">
                <!-- Informations + carte -->
            </div>
            <form class="contact-form">
                <!-- Formulaire avec upload -->
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <!-- Liens légaux, réseaux sociaux -->
    </footer>
</body>
</html>
```

---

## 🎯 MOYENS TECHNIQUES DÉTAILLÉS

### 1. CSS MODERNE (Architecture BEM)

```css
/* VARIABLES CSS POUR COHÉRENCE */
:root {
    --color-primary: #D4896B;      /* Terracotta */
    --color-secondary: #9CAF9A;    /* Vert sauge */
    --color-accent: #8B6F47;       /* Brun chaud */
    --color-neutral: #F5F1EB;      /* Beige */
    --font-serif: 'Playfair Display', serif;
    --font-sans: 'Inter', sans-serif;
    --spacing-unit: 1rem;
    --border-radius: 8px;
    --transition: 0.3s ease;
}

/* GRID SYSTEM PERSONNALISÉ */
.container {
    display: grid;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-unit);
    gap: calc(var(--spacing-unit) * 2);
}

/* COMPOSANTS RÉUTILISABLES */
.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}
```

### 2. ANIMATIONS CSS AVANCÉES

```css
/* ANIMATION HERO : Fils qui se tissent */
@keyframes weaving {
    0% { stroke-dashoffset: 1000; }
    100% { stroke-dashoffset: 0; }
}

.hero-visual svg path {
    stroke-dasharray: 1000;
    animation: weaving 3s ease-in-out infinite alternate;
}

/* INTERSECTION OBSERVER ANIMATIONS */
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* GALERIE HOVER EFFECTS */
.gallery-item {
    position: relative;
    overflow: hidden;
}

.gallery-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.gallery-item:hover::before {
    left: 100%;
}
```

### 3. JAVASCRIPT MODERNE (ES6+)

```javascript
// GESTION PRINCIPALE DU SITE
class RemmailleuseApp {
    constructor() {
        this.initializeApp();
    }

    async initializeApp() {
        await this.loadContent();
        this.setupNavigation();
        this.initializeGallery();
        this.setupForms();
        this.setupAnimations();
    }

    // CHARGEMENT DYNAMIQUE DU CONTENU
    async loadContent() {
        try {
            const [content, services] = await Promise.all([
                fetch('data/content.json').then(r => r.json()),
                fetch('data/services.json').then(r => r.json())
            ]);
            
            this.renderContent(content);
            this.renderServices(services);
        } catch (error) {
            console.error('Erreur chargement:', error);
        }
    }

    // GALERIE INTERACTIVE
    initializeGallery() {
        const gallery = new GalleryManager({
            container: '.gallery-grid',
            modal: '.gallery-modal',
            filters: '.gallery-filters'
        });
    }
}

// GESTIONNAIRE DE GALERIE
class GalleryManager {
    constructor(options) {
        this.container = document.querySelector(options.container);
        this.modal = document.querySelector(options.modal);
        this.setupLightbox();
        this.setupFilters();
    }

    setupLightbox() {
        this.container.addEventListener('click', (e) => {
            if (e.target.matches('.gallery-item img')) {
                this.openModal(e.target);
            }
        });
    }

    openModal(image) {
        const modal = this.modal;
        const modalImg = modal.querySelector('img');
        
        modal.style.display = 'flex';
        modalImg.src = image.src;
        modalImg.alt = image.alt;
        
        // Animation d'ouverture
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });
    }
}
```

### 4. SYSTÈME DE CONTENU DYNAMIQUE

```json
// data/content.json
{
    "hero": {
        "title": "L'art de redonner vie à vos tissus précieux",
        "subtitle": "Remaillage traditionnel & réparation invisible",
        "cta": "Découvrir mon savoir-faire"
    },
    "expertise": {
        "intro": "Passionnée par les techniques traditionnelles...",
        "process": [
            {
                "step": 1,
                "title": "Diagnostic",
                "description": "Analyse minutieuse de la pièce...",
                "icon": "🔍"
            }
        ]
    }
}

// data/services.json
{
    "services": [
        {
            "id": "remaillage",
            "name": "Remaillage classique",
            "description": "Reconstruction maille par maille",
            "price_from": 15,
            "price_to": 40,
            "duration": "2-5 jours",
            "image": "assets/images/services/remaillage.jpg"
        }
    ]
}
```

### 5. FORMULAIRES AVANCÉS

```javascript
// GESTION FORMULAIRE AVEC UPLOAD
class ContactForm {
    constructor() {
        this.form = document.querySelector('.contact-form');
        this.setupValidation();
        this.setupFileUpload();
    }

    setupFileUpload() {
        const dropZone = this.form.querySelector('.drop-zone');
        const fileInput = this.form.querySelector('input[type="file"]');

        // Drag & Drop
        dropZone.addEventListener('dragover', this.handleDragOver);
        dropZone.addEventListener('drop', this.handleDrop);
        
        // Preview des images
        fileInput.addEventListener('change', this.previewFiles);
    }

    handleDrop(e) {
        e.preventDefault();
        const files = Array.from(e.dataTransfer.files);
        this.processFiles(files);
    }

    async submitForm(formData) {
        // Envoi via EmailJS ou Netlify Forms
        try {
            const response = await emailjs.send(
                'service_id',
                'template_id',
                formData
            );
            this.showSuccess();
        } catch (error) {
            this.showError(error);
        }
    }
}
```

---

## 🔧 OUTILS & TECHNOLOGIES

### Frontend Stack
- **HTML5** : Sémantique, microdata pour SEO
- **CSS3** : Grid, Flexbox, Custom Properties, animations
- **JavaScript ES6+** : Modules, async/await, classes
- **SVG** : Icônes et animations vectorielles

### Optimisations Performance
- **Lazy Loading** : Images et contenu below-the-fold
- **Critical CSS** : Styles critiques inline
- **Image Optimization** : WebP avec fallback, responsive images
- **Code Splitting** : JS modulaire chargé selon besoins

### Outils de Développement
- **Sass/SCSS** : Préprocesseur CSS (optionnel)
- **Autoprefixer** : Compatibilité navigateurs
- **Webpack/Vite** : Bundling et optimisation (optionnel)
- **Git** : Versioning du code

### Services Externes
- **EmailJS** : Envoi d'emails sans backend
- **Netlify/Vercel** : Déploiement et hosting
- **Google Maps API** : Cartes interactives
- **Cloudinary** : Optimisation images (optionnel)

---

## 📱 RESPONSIVE DESIGN

### Breakpoints
```css
/* Mobile First Approach */
:root {
    --bp-sm: 480px;   /* Petits téléphones */
    --bp-md: 768px;   /* Tablettes */
    --bp-lg: 1024px;  /* Desktop */
    --bp-xl: 1200px;  /* Large desktop */
}

@media (min-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

### Navigation Adaptive
- **Mobile** : Menu hamburger avec slide-out
- **Desktop** : Navigation horizontale fixe
- **Touch Gestures** : Swipe pour galerie mobile

---

## 🚀 DÉPLOIEMENT & MAINTENANCE

### Configuration Hébergement
```
# .htaccess pour Apache (Infomaniak)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Cache des assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Système de Mise à Jour Simple
- **Interface admin** : Formulaires pour éditer content.json
- **Git automation** : Push automatique des changements
- **Backup** : Sauvegarde quotidienne des données

Cette structure garantit un site moderne, performant et facilement maintenable ! 🎯