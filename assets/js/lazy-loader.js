/**
 * ===== LAZY LOADER - SITE REMMAILLEUSE =====
 * Système de chargement paresseux pour images et composants
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class LazyLoader {
    constructor(options = {}) {
        this.options = {
            imageSelector: options.imageSelector || '[data-lazy]',
            componentSelector: options.componentSelector || '[data-lazy-component]',
            rootMargin: options.rootMargin || '50px',
            threshold: options.threshold || 0.1,
            enableImages: options.enableImages !== false,
            enableComponents: options.enableComponents !== false,
            enablePreload: options.enablePreload !== false,
            fadeInDuration: options.fadeInDuration || 300,
            placeholder: options.placeholder || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjZjVmNWY1Ii8+CjxjaXJjbGUgY3g9IjIwMCIgY3k9IjE1MCIgcj0iMjAiIGZpbGw9IiNjY2MiLz4KPC9zdmc+',
            debug: options.debug || false
        };
        
        this.imageObserver = null;
        this.componentObserver = null;
        this.loadedImages = new Set();
        this.loadedComponents = new Set();
        this.preloadedImages = new Set();
        this.stats = {
            images: { total: 0, loaded: 0, errors: 0 },
            components: { total: 0, loaded: 0, errors: 0 }
        };
        
        this.init();
    }
    
    init() {
        if (!this.isIntersectionObserverSupported()) {
            this.log('IntersectionObserver non supporté - chargement normal');
            this.loadAllFallback();
            return;
        }
        
        this.setupObservers();
        this.observeElements();
        this.setupPreloading();
        this.setupEventListeners();
        
        this.log('Lazy loader initialisé');
    }
    
    /**
     * Vérifier le support d'IntersectionObserver
     */
    isIntersectionObserverSupported() {
        return 'IntersectionObserver' in window;
    }
    
    /**
     * Configurer les observers
     */
    setupObservers() {
        const observerOptions = {
            rootMargin: this.options.rootMargin,
            threshold: this.options.threshold
        };
        
        if (this.options.enableImages) {
            this.imageObserver = new IntersectionObserver(
                this.handleImageIntersection.bind(this),
                observerOptions
            );
        }
        
        if (this.options.enableComponents) {
            this.componentObserver = new IntersectionObserver(
                this.handleComponentIntersection.bind(this),
                observerOptions
            );
        }
    }
    
    /**
     * Observer les éléments existants
     */
    observeElements() {
        if (this.options.enableImages) {
            this.observeImages();
        }
        
        if (this.options.enableComponents) {
            this.observeComponents();
        }
    }
    
    /**
     * Observer les images
     */
    observeImages() {
        const images = document.querySelectorAll(this.options.imageSelector);
        
        images.forEach(img => {
            this.stats.images.total++;
            this.setupImagePlaceholder(img);
            this.imageObserver.observe(img);
        });
        
        this.log(`${images.length} images ajoutées à l'observation`);
    }
    
    /**
     * Observer les composants
     */
    observeComponents() {
        const components = document.querySelectorAll(this.options.componentSelector);
        
        components.forEach(component => {
            this.stats.components.total++;
            this.setupComponentPlaceholder(component);
            this.componentObserver.observe(component);
        });
        
        this.log(`${components.length} composants ajoutés à l'observation`);
    }
    
    /**
     * Configurer le placeholder d'image
     */
    setupImagePlaceholder(img) {
        if (!img.src || img.src === '') {
            img.src = this.options.placeholder;
        }
        
        img.style.opacity = '0';
        img.style.transition = `opacity ${this.options.fadeInDuration}ms ease`;
        
        // Ajouter une classe pour le CSS
        img.classList.add('lazy-loading');
    }
    
    /**
     * Configurer le placeholder de composant
     */
    setupComponentPlaceholder(component) {
        if (!component.innerHTML.trim()) {
            component.innerHTML = this.getComponentPlaceholder();
        }
        
        component.style.opacity = '0';
        component.style.transition = `opacity ${this.options.fadeInDuration}ms ease`;
        component.classList.add('lazy-loading');
    }
    
    /**
     * Obtenir le placeholder HTML pour un composant
     */
    getComponentPlaceholder() {
        return `
            <div class="lazy-placeholder">
                <div class="lazy-spinner">
                    <div class="spinner"></div>
                </div>
                <p>Chargement...</p>
            </div>
        `;
    }
    
    /**
     * Gérer l'intersection des images
     */
    handleImageIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadImage(entry.target);
                this.imageObserver.unobserve(entry.target);
            }
        });
    }
    
    /**
     * Gérer l'intersection des composants
     */
    handleComponentIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadComponent(entry.target);
                this.componentObserver.unobserve(entry.target);
            }
        });
    }
    
    /**
     * Charger une image
     */
    loadImage(img) {
        if (this.loadedImages.has(img)) return;
        
        const src = img.dataset.lazy;
        if (!src) return;
        
        this.log(`Chargement image: ${src}`);
        
        const loadPromise = this.createImageLoadPromise(src);
        
        loadPromise
            .then((loadedImg) => {
                this.onImageLoaded(img, loadedImg);
            })
            .catch((error) => {
                this.onImageError(img, error);
            });
    }
    
    /**
     * Créer une promesse de chargement d'image
     */
    createImageLoadPromise(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            
            img.onload = () => {
                resolve(img);
            };
            
            img.onerror = () => {
                reject(new Error(`Erreur de chargement: ${src}`));
            };
            
            img.src = src;
        });
    }
    
    /**
     * Gestion du succès de chargement d'image
     */
    onImageLoaded(img, loadedImg) {
        this.loadedImages.add(img);
        this.stats.images.loaded++;
        
        // Copier les attributs importants
        if (loadedImg.width && loadedImg.height) {
            img.width = loadedImg.width;
            img.height = loadedImg.height;
        }
        
        // Changer la source
        img.src = loadedImg.src;
        
        // Supprimer les attributs de lazy loading
        delete img.dataset.lazy;
        img.classList.remove('lazy-loading');
        img.classList.add('lazy-loaded');
        
        // Fade in
        img.style.opacity = '1';
        
        // Déclencher un événement
        this.dispatchEvent(img, 'lazyloaded');
        
        this.log(`Image chargée: ${loadedImg.src}`);
    }
    
    /**
     * Gestion des erreurs de chargement d'image
     */
    onImageError(img, error) {
        this.stats.images.errors++;
        
        img.classList.remove('lazy-loading');
        img.classList.add('lazy-error');
        
        // Image d'erreur
        img.src = this.getErrorImagePlaceholder();
        img.style.opacity = '1';
        
        this.dispatchEvent(img, 'lazyerror', { error });
        
        this.log(`Erreur chargement image: ${error.message}`);
    }
    
    /**
     * Obtenir le placeholder d'erreur d'image
     */
    getErrorImagePlaceholder() {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjZjVmNWY1IiBzdHJva2U9IiNlMGUwZTAiLz4KPGNpcmNsZSBjeD0iMjAwIiBjeT0iMTUwIiByPSIyMCIgZmlsbD0iI2ZmNjY2NiIvPgo8dGV4dCB4PSIyMDAiIHk9IjE4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OTk5OSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0Ij5FcnJldXI8L3RleHQ+Cjwvc3ZnPg==';
    }
    
    /**
     * Charger un composant
     */
    loadComponent(component) {
        if (this.loadedComponents.has(component)) return;
        
        const componentType = component.dataset.lazyComponent;
        const componentData = component.dataset.lazyData;
        
        if (!componentType) return;
        
        this.log(`Chargement composant: ${componentType}`);
        
        this.loadComponentByType(component, componentType, componentData)
            .then(() => {
                this.onComponentLoaded(component);
            })
            .catch((error) => {
                this.onComponentError(component, error);
            });
    }
    
    /**
     * Charger un composant par type
     */
    async loadComponentByType(component, type, data) {
        const parsedData = data ? JSON.parse(data) : {};
        
        switch (type) {
            case 'gallery':
                return this.loadGalleryComponent(component, parsedData);
            case 'contact-form':
                return this.loadContactFormComponent(component, parsedData);
            case 'price-calculator':
                return this.loadPriceCalculatorComponent(component, parsedData);
            case 'testimonials':
                return this.loadTestimonialsComponent(component, parsedData);
            default:
                throw new Error(`Type de composant inconnu: ${type}`);
        }
    }
    
    /**
     * Charger le composant galerie
     */
    async loadGalleryComponent(component, data) {
        const galleryData = await this.fetchData('/data/gallery.json');
        
        const galleryHtml = `
            <div class="gallery-lazy-loaded">
                <div class="gallery-filters" id="gallery-filters">
                    ${galleryData.categories.map(cat => `
                        <button class="filter-btn ${cat.active ? 'active' : ''}" 
                                data-category="${cat.id}">
                            ${cat.name}
                        </button>
                    `).join('')}
                </div>
                <div class="gallery-grid" id="gallery-grid">
                    ${galleryData.items.map(item => `
                        <div class="gallery-item scroll-reveal" 
                             data-category="${item.category}">
                            <div class="gallery-image">
                                <img data-lazy="${item.image}" alt="${item.title}">
                                <div class="gallery-overlay">
                                    <div class="gallery-overlay-text">
                                        <div>VOIR PLUS</div>
                                    </div>
                                </div>
                            </div>
                            <div class="gallery-info">
                                <h4>${item.title}</h4>
                                <p>${item.description}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        component.innerHTML = galleryHtml;
        
        // Réobserver les nouvelles images
        this.observeNewImages(component);
    }
    
    /**
     * Charger le composant formulaire de contact
     */
    async loadContactFormComponent(component, data) {
        const formHtml = `
            <div class="contact-form-lazy-loaded">
                <form id="contact-form" class="contact-form">
                    <div class="form-group">
                        <label for="firstname">Prénom *</label>
                        <input type="text" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Nom *</label>
                        <input type="text" id="lastname" name="lastname" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Envoyer
                    </button>
                </form>
            </div>
        `;
        
        component.innerHTML = formHtml;
        
        // Initialiser le formulaire
        this.initializeContactForm(component);
    }
    
    /**
     * Charger les autres composants...
     */
    async loadPriceCalculatorComponent(component, data) {
        // Implémentation du calculateur de prix
        component.innerHTML = '<div class="price-calculator">Calculateur de prix chargé</div>';
    }
    
    async loadTestimonialsComponent(component, data) {
        // Implémentation des témoignages
        component.innerHTML = '<div class="testimonials">Témoignages chargés</div>';
    }
    
    /**
     * Gestion du succès de chargement de composant
     */
    onComponentLoaded(component) {
        this.loadedComponents.add(component);
        this.stats.components.loaded++;
        
        component.classList.remove('lazy-loading');
        component.classList.add('lazy-loaded');
        
        // Fade in
        component.style.opacity = '1';
        
        this.dispatchEvent(component, 'lazyloaded');
        
        this.log(`Composant chargé: ${component.dataset.lazyComponent}`);
    }
    
    /**
     * Gestion des erreurs de chargement de composant
     */
    onComponentError(component, error) {
        this.stats.components.errors++;
        
        component.classList.remove('lazy-loading');
        component.classList.add('lazy-error');
        
        component.innerHTML = `
            <div class="component-error">
                <p>Erreur de chargement du composant</p>
                <button onclick="this.parentElement.parentElement.dispatchEvent(new CustomEvent('retry'))">
                    Réessayer
                </button>
            </div>
        `;
        
        component.style.opacity = '1';
        
        this.dispatchEvent(component, 'lazyerror', { error });
        
        this.log(`Erreur chargement composant: ${error.message}`);
    }
    
    /**
     * Configurer le préchargement
     */
    setupPreloading() {
        if (!this.options.enablePreload) return;
        
        // Précharger les images au-dessus du pli
        this.preloadAboveFoldImages();
        
        // Précharger les images au survol
        this.setupHoverPreloading();
    }
    
    /**
     * Précharger les images au-dessus du pli
     */
    preloadAboveFoldImages() {
        const images = document.querySelectorAll(this.options.imageSelector);
        const viewport = window.innerHeight;
        
        images.forEach(img => {
            const rect = img.getBoundingClientRect();
            if (rect.top < viewport) {
                this.preloadImage(img);
            }
        });
    }
    
    /**
     * Configurer le préchargement au survol
     */
    setupHoverPreloading() {
        document.addEventListener('mouseover', (e) => {
            const img = e.target.closest(this.options.imageSelector);
            if (img && !this.preloadedImages.has(img)) {
                this.preloadImage(img);
            }
        });
    }
    
    /**
     * Précharger une image
     */
    preloadImage(img) {
        if (this.preloadedImages.has(img)) return;
        
        const src = img.dataset.lazy;
        if (!src) return;
        
        this.preloadedImages.add(img);
        
        const preloadImg = new Image();
        preloadImg.src = src;
        
        this.log(`Préchargement: ${src}`);
    }
    
    /**
     * Configurer les événements
     */
    setupEventListeners() {
        // Réobserver les éléments ajoutés dynamiquement
        this.observeNewContent();
        
        // Gestion du redimensionnement
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));
        
        // Gestion des erreurs de réseau
        window.addEventListener('online', () => {
            this.retryFailedLoads();
        });
    }
    
    /**
     * Observer le nouveau contenu ajouté
     */
    observeNewContent() {
        if (!('MutationObserver' in window)) return;
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        this.observeNewImages(node);
                        this.observeNewComponents(node);
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Observer les nouvelles images
     */
    observeNewImages(container) {
        if (!this.options.enableImages || !this.imageObserver) return;
        
        const images = container.querySelectorAll(this.options.imageSelector);
        
        images.forEach(img => {
            if (!this.loadedImages.has(img)) {
                this.stats.images.total++;
                this.setupImagePlaceholder(img);
                this.imageObserver.observe(img);
            }
        });
    }
    
    /**
     * Observer les nouveaux composants
     */
    observeNewComponents(container) {
        if (!this.options.enableComponents || !this.componentObserver) return;
        
        const components = container.querySelectorAll(this.options.componentSelector);
        
        components.forEach(component => {
            if (!this.loadedComponents.has(component)) {
                this.stats.components.total++;
                this.setupComponentPlaceholder(component);
                this.componentObserver.observe(component);
            }
        });
    }
    
    /**
     * Gérer le redimensionnement
     */
    handleResize() {
        // Réinitialiser le préchargement des images au-dessus du pli
        this.preloadAboveFoldImages();
    }
    
    /**
     * Réessayer les chargements échoués
     */
    retryFailedLoads() {
        // Réessayer les images en erreur
        const errorImages = document.querySelectorAll(`${this.options.imageSelector}.lazy-error`);
        errorImages.forEach(img => {
            img.classList.remove('lazy-error');
            this.loadImage(img);
        });
        
        // Réessayer les composants en erreur
        const errorComponents = document.querySelectorAll(`${this.options.componentSelector}.lazy-error`);
        errorComponents.forEach(component => {
            component.classList.remove('lazy-error');
            this.loadComponent(component);
        });
    }
    
    /**
     * Fallback pour les navigateurs non supportés
     */
    loadAllFallback() {
        // Charger toutes les images
        const images = document.querySelectorAll(this.options.imageSelector);
        images.forEach(img => {
            const src = img.dataset.lazy;
            if (src) {
                img.src = src;
                delete img.dataset.lazy;
            }
        });
        
        // Charger tous les composants
        const components = document.querySelectorAll(this.options.componentSelector);
        components.forEach(component => {
            this.loadComponent(component);
        });
    }
    
    /**
     * Méthodes utilitaires
     */
    
    fetchData(url) {
        return fetch(url).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        });
    }
    
    initializeContactForm(container) {
        const form = container.querySelector('#contact-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                // Gestion du formulaire
                if (window.remmailleuseApp) {
                    window.remmailleuseApp.handleContactForm(e);
                }
            });
        }
    }
    
    dispatchEvent(element, eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            bubbles: true,
            cancelable: true,
            detail
        });
        element.dispatchEvent(event);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    log(...args) {
        if (this.options.debug) {
            console.log('[LazyLoader]', ...args);
        }
    }
    
    /**
     * API publique
     */
    
    getStats() {
        return {
            images: {
                total: this.stats.images.total,
                loaded: this.stats.images.loaded,
                errors: this.stats.images.errors,
                loadRate: this.stats.images.total > 0 ? 
                    Math.round((this.stats.images.loaded / this.stats.images.total) * 100) : 0
            },
            components: {
                total: this.stats.components.total,
                loaded: this.stats.components.loaded,
                errors: this.stats.components.errors,
                loadRate: this.stats.components.total > 0 ? 
                    Math.round((this.stats.components.loaded / this.stats.components.total) * 100) : 0
            }
        };
    }
    
    loadAll() {
        this.loadAllFallback();
    }
    
    destroy() {
        if (this.imageObserver) {
            this.imageObserver.disconnect();
        }
        
        if (this.componentObserver) {
            this.componentObserver.disconnect();
        }
        
        this.log('Lazy loader détruit');
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.lazyLoader = new LazyLoader({
        enableImages: true,
        enableComponents: true,
        enablePreload: true,
        debug: false
    });
});

// CSS pour les animations
const lazyStyles = `
    <style>
        .lazy-loading {
            opacity: 0;
            transition: opacity 300ms ease;
        }
        
        .lazy-loaded {
            opacity: 1;
        }
        
        .lazy-error {
            opacity: 0.5;
        }
        
        .lazy-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .lazy-spinner .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .component-error {
            padding: 20px;
            text-align: center;
            background: #ffe6e6;
            border: 1px solid #ffcccc;
            border-radius: 8px;
        }
        
        .component-error button {
            margin-top: 10px;
            padding: 8px 16px;
            background: #ff6666;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .component-error button:hover {
            background: #ff5555;
        }
    </style>
`;

// Ajouter les styles
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', lazyStyles);
}