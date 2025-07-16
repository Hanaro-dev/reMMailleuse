/**
 * ===== OPTIMISEUR DE BUNDLES - SITE REMMAILLEUSE =====
 * Optimisation intelligente des bundles CSS et JS
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class BundleOptimizer {
    constructor(options = {}) {
        this.options = {
            enableCodeSplitting: options.enableCodeSplitting !== false,
            enableTreeShaking: options.enableTreeShaking !== false,
            enablePreloading: options.enablePreloading !== false,
            enableCompression: options.enableCompression !== false,
            chunkSize: options.chunkSize || 50000, // 50KB
            debug: options.debug || false
        };
        
        this.bundles = new Map();
        this.loadedModules = new Set();
        this.pendingModules = new Map();
        this.moduleRegistry = new Map();
        this.stats = {
            totalModules: 0,
            loadedModules: 0,
            bundleSize: 0,
            compressionRatio: 0
        };
        
        this.init();
    }
    
    init() {
        this.registerCoreModules();
        this.setupDynamicImports();
        this.setupPreloading();
        this.setupResourceHints();
        
        this.log('Bundle optimizer initialisé');
    }
    
    /**
     * Enregistrer les modules de base
     */
    registerCoreModules() {
        // Modules critiques qui doivent être chargés immédiatement
        this.registerModule('cache', {
            src: '/assets/js/cache.js',
            critical: true,
            dependencies: [],
            size: 15000
        });
        
        this.registerModule('lazy-loader', {
            src: '/assets/js/lazy-loader.js',
            critical: true,
            dependencies: [],
            size: 25000
        });
        
        this.registerModule('main', {
            src: '/assets/js/main.js',
            critical: true,
            dependencies: ['cache', 'lazy-loader'],
            size: 35000
        });
        
        // Modules non-critiques qui peuvent être chargés à la demande
        this.registerModule('analytics', {
            src: '/assets/js/analytics.js',
            critical: false,
            dependencies: [],
            size: 8000,
            lazy: true
        });
        
        this.registerModule('gallery', {
            src: '/assets/js/gallery.js',
            critical: false,
            dependencies: ['lazy-loader'],
            size: 12000,
            lazy: true
        });
        
        this.registerModule('forms', {
            src: '/assets/js/forms.js',
            critical: false,
            dependencies: ['main'],
            size: 18000,
            lazy: true
        });
        
        this.registerModule('animations', {
            src: '/assets/js/animations.js',
            critical: false,
            dependencies: [],
            size: 10000,
            lazy: true
        });
    }
    
    /**
     * Enregistrer un module
     */
    registerModule(name, config) {
        this.moduleRegistry.set(name, {
            name,
            src: config.src,
            critical: config.critical || false,
            dependencies: config.dependencies || [],
            size: config.size || 0,
            lazy: config.lazy || false,
            loaded: false,
            loading: false,
            error: null
        });
        
        this.stats.totalModules++;
    }
    
    /**
     * Configurer les imports dynamiques
     */
    setupDynamicImports() {
        // Intercepter les clics pour précharger les modules nécessaires
        document.addEventListener('click', (e) => {
            this.handleClick(e);
        });
        
        // Intercepter les survols pour précharger
        document.addEventListener('mouseover', (e) => {
            this.handleHover(e);
        });
        
        // Intercepter les changements d'URL pour charger les modules de page
        window.addEventListener('popstate', () => {
            this.loadPageModules();
        });
    }
    
    /**
     * Gérer les clics
     */
    handleClick(event) {
        const target = event.target.closest('[data-module]');
        if (!target) return;
        
        const moduleName = target.dataset.module;
        if (moduleName) {
            this.loadModule(moduleName);
        }
    }
    
    /**
     * Gérer les survols
     */
    handleHover(event) {
        const target = event.target.closest('[data-preload]');
        if (!target) return;
        
        const moduleName = target.dataset.preload;
        if (moduleName) {
            this.preloadModule(moduleName);
        }
    }
    
    /**
     * Charger les modules de page
     */
    loadPageModules() {
        const path = window.location.pathname;
        const hash = window.location.hash;
        
        // Charger les modules selon la section
        if (hash.includes('gallery') || path.includes('gallery')) {
            this.loadModule('gallery');
        }
        
        if (hash.includes('contact') || path.includes('contact')) {
            this.loadModule('forms');
        }
        
        if (hash.includes('services') || path.includes('services')) {
            this.loadModule('animations');
        }
    }
    
    /**
     * Charger un module
     */
    async loadModule(moduleName) {
        const module = this.moduleRegistry.get(moduleName);
        if (!module) {
            this.log(`Module introuvable: ${moduleName}`);
            return null;
        }
        
        if (module.loaded) {
            this.log(`Module déjà chargé: ${moduleName}`);
            return module;
        }
        
        if (module.loading) {
            this.log(`Module en cours de chargement: ${moduleName}`);
            return this.pendingModules.get(moduleName);
        }
        
        this.log(`Chargement du module: ${moduleName}`);
        
        // Marquer comme en cours de chargement
        module.loading = true;
        
        try {
            // Charger les dépendances en premier
            await this.loadDependencies(module);
            
            // Charger le module lui-même
            const loadPromise = this.loadScript(module.src);
            this.pendingModules.set(moduleName, loadPromise);
            
            await loadPromise;
            
            // Marquer comme chargé
            module.loaded = true;
            module.loading = false;
            this.loadedModules.add(moduleName);
            this.stats.loadedModules++;
            
            this.pendingModules.delete(moduleName);
            
            this.log(`Module chargé avec succès: ${moduleName}`);
            
            // Déclencher un événement
            this.dispatchEvent('moduleLoaded', { module: moduleName });
            
            return module;
            
        } catch (error) {
            module.loading = false;
            module.error = error;
            this.pendingModules.delete(moduleName);
            
            this.log(`Erreur chargement module ${moduleName}:`, error);
            
            this.dispatchEvent('moduleError', { module: moduleName, error });
            
            throw error;
        }
    }
    
    /**
     * Charger les dépendances d'un module
     */
    async loadDependencies(module) {
        if (!module.dependencies || module.dependencies.length === 0) {
            return;
        }
        
        this.log(`Chargement des dépendances pour ${module.name}:`, module.dependencies);
        
        const dependencyPromises = module.dependencies.map(dep => this.loadModule(dep));
        await Promise.all(dependencyPromises);
    }
    
    /**
     * Charger un script
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            // Vérifier si le script est déjà chargé
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.defer = true;
            
            script.onload = () => {
                this.log(`Script chargé: ${src}`);
                resolve();
            };
            
            script.onerror = () => {
                this.log(`Erreur chargement script: ${src}`);
                reject(new Error(`Erreur de chargement: ${src}`));
            };
            
            // Ajouter des attributs d'optimisation
            script.setAttribute('data-turbo-track', 'reload');
            
            document.head.appendChild(script);
        });
    }
    
    /**
     * Précharger un module
     */
    async preloadModule(moduleName) {
        const module = this.moduleRegistry.get(moduleName);
        if (!module || module.loaded || module.loading) {
            return;
        }
        
        this.log(`Préchargement du module: ${moduleName}`);
        
        // Utiliser les resource hints pour précharger
        this.addResourceHint(module.src, 'preload', 'script');
        
        // Précharger les dépendances aussi
        if (module.dependencies) {
            module.dependencies.forEach(dep => {
                this.preloadModule(dep);
            });
        }
    }
    
    /**
     * Configurer le préchargement
     */
    setupPreloading() {
        if (!this.options.enablePreloading) return;
        
        // Précharger les modules critiques
        this.preloadCriticalModules();
        
        // Précharger selon les actions utilisateur
        this.setupIntelligentPreloading();
    }
    
    /**
     * Précharger les modules critiques
     */
    preloadCriticalModules() {
        for (const [name, module] of this.moduleRegistry) {
            if (module.critical && !module.loaded) {
                this.preloadModule(name);
            }
        }
    }
    
    /**
     * Préchargement intelligent
     */
    setupIntelligentPreloading() {
        // Précharger selon le scroll
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Précharger selon la taille de l'écran
        this.preloadForViewport();
    }
    
    /**
     * Gérer le scroll pour le préchargement
     */
    handleScroll() {
        const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        
        // Précharger la galerie si on scroll vers le milieu
        if (scrollPercent > 30 && !this.loadedModules.has('gallery')) {
            this.preloadModule('gallery');
        }
        
        // Précharger les formulaires si on scroll vers le bas
        if (scrollPercent > 60 && !this.loadedModules.has('forms')) {
            this.preloadModule('forms');
        }
        
        // Précharger les animations si on scroll vers le bas
        if (scrollPercent > 40 && !this.loadedModules.has('animations')) {
            this.preloadModule('animations');
        }
    }
    
    /**
     * Précharger selon la taille de l'écran
     */
    preloadForViewport() {
        const isDesktop = window.innerWidth >= 1024;
        const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
        const isMobile = window.innerWidth < 768;
        
        if (isDesktop) {
            // Sur desktop, précharger plus agressivement
            setTimeout(() => {
                this.preloadModule('gallery');
                this.preloadModule('forms');
            }, 2000);
        } else if (isTablet) {
            // Sur tablette, précharger modérément
            setTimeout(() => {
                this.preloadModule('gallery');
            }, 3000);
        } else if (isMobile) {
            // Sur mobile, précharger minimalement
            // Attendre l'interaction utilisateur
        }
    }
    
    /**
     * Configurer les resource hints
     */
    setupResourceHints() {
        // DNS prefetch pour les domaines externes
        this.addResourceHint('//fonts.googleapis.com', 'dns-prefetch');
        this.addResourceHint('//fonts.gstatic.com', 'dns-prefetch');
        
        // Preconnect pour les ressources critiques
        this.addResourceHint('//fonts.googleapis.com', 'preconnect');
        
        // Prefetch pour les ressources probables
        this.addResourceHint('/data/gallery.json', 'prefetch', 'fetch');
        this.addResourceHint('/data/services.json', 'prefetch', 'fetch');
    }
    
    /**
     * Ajouter un resource hint
     */
    addResourceHint(href, rel, as = null) {
        const existing = document.querySelector(`link[href="${href}"][rel="${rel}"]`);
        if (existing) return;
        
        const link = document.createElement('link');
        link.rel = rel;
        link.href = href;
        
        if (as) {
            link.as = as;
        }
        
        document.head.appendChild(link);
    }
    
    /**
     * Créer des bundles optimisés
     */
    createOptimizedBundles() {
        const bundles = {
            critical: [],
            lazy: [],
            vendor: []
        };
        
        for (const [name, module] of this.moduleRegistry) {
            if (module.critical) {
                bundles.critical.push(module);
            } else if (module.lazy) {
                bundles.lazy.push(module);
            } else {
                bundles.vendor.push(module);
            }
        }
        
        return bundles;
    }
    
    /**
     * Obtenir les statistiques
     */
    getStats() {
        const totalSize = Array.from(this.moduleRegistry.values())
            .reduce((sum, module) => sum + module.size, 0);
        
        const loadedSize = Array.from(this.moduleRegistry.values())
            .filter(module => module.loaded)
            .reduce((sum, module) => sum + module.size, 0);
        
        return {
            totalModules: this.stats.totalModules,
            loadedModules: this.stats.loadedModules,
            pendingModules: this.pendingModules.size,
            totalSize,
            loadedSize,
            loadedPercent: this.stats.totalModules > 0 ? 
                Math.round((this.stats.loadedModules / this.stats.totalModules) * 100) : 0,
            sizePercent: totalSize > 0 ? 
                Math.round((loadedSize / totalSize) * 100) : 0,
            modules: Array.from(this.moduleRegistry.values()).map(module => ({
                name: module.name,
                loaded: module.loaded,
                loading: module.loading,
                size: module.size,
                critical: module.critical,
                lazy: module.lazy,
                error: module.error
            }))
        };
    }
    
    /**
     * Forcer le chargement de tous les modules
     */
    async loadAllModules() {
        this.log('Chargement forcé de tous les modules');
        
        const loadPromises = Array.from(this.moduleRegistry.keys())
            .map(name => this.loadModule(name).catch(error => {
                this.log(`Erreur chargement module ${name}:`, error);
                return null;
            }));
        
        const results = await Promise.all(loadPromises);
        
        this.log('Tous les modules chargés:', results.filter(Boolean).length);
        
        return results;
    }
    
    /**
     * Nettoyer les modules non utilisés
     */
    cleanupUnusedModules() {
        // Identifier les modules non utilisés depuis 5 minutes
        const unusedModules = [];
        const fiveMinutesAgo = Date.now() - 5 * 60 * 1000;
        
        for (const [name, module] of this.moduleRegistry) {
            if (module.loaded && module.lastUsed && module.lastUsed < fiveMinutesAgo) {
                unusedModules.push(name);
            }
        }
        
        // Supprimer les scripts non utilisés
        unusedModules.forEach(name => {
            const module = this.moduleRegistry.get(name);
            if (module && !module.critical) {
                const script = document.querySelector(`script[src="${module.src}"]`);
                if (script) {
                    script.remove();
                    module.loaded = false;
                    this.loadedModules.delete(name);
                    this.stats.loadedModules--;
                    this.log(`Module nettoyé: ${name}`);
                }
            }
        });
        
        return unusedModules.length;
    }
    
    /**
     * Méthodes utilitaires
     */
    
    dispatchEvent(eventName, detail) {
        const event = new CustomEvent(`bundleOptimizer.${eventName}`, {
            detail,
            bubbles: true
        });
        document.dispatchEvent(event);
    }
    
    log(...args) {
        if (this.options.debug) {
            console.log('[BundleOptimizer]', ...args);
        }
    }
    
    /**
     * API publique
     */
    
    // Charger un module spécifique
    async load(moduleName) {
        return this.loadModule(moduleName);
    }
    
    // Précharger un module
    async preload(moduleName) {
        return this.preloadModule(moduleName);
    }
    
    // Vérifier si un module est chargé
    isLoaded(moduleName) {
        return this.loadedModules.has(moduleName);
    }
    
    // Obtenir les informations d'un module
    getModule(moduleName) {
        return this.moduleRegistry.get(moduleName);
    }
    
    // Obtenir tous les modules
    getModules() {
        return Array.from(this.moduleRegistry.values());
    }
    
    // Détruire l'optimiseur
    destroy() {
        this.moduleRegistry.clear();
        this.loadedModules.clear();
        this.pendingModules.clear();
        this.bundles.clear();
        
        this.log('Bundle optimizer détruit');
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.bundleOptimizer = new BundleOptimizer({
        enableCodeSplitting: true,
        enableTreeShaking: true,
        enablePreloading: true,
        enableCompression: true,
        debug: false
    });
    
    // Charger les modules critiques
    window.bundleOptimizer.loadPageModules();
});

// Nettoyage périodique
setInterval(() => {
    if (window.bundleOptimizer) {
        window.bundleOptimizer.cleanupUnusedModules();
    }
}, 5 * 60 * 1000); // Toutes les 5 minutes