/**
 * ===== GESTIONNAIRE DE CACHE CLIENT - SITE REMMAILLEUSE =====
 * Cache intelligent côté client pour améliorer les performances
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class ClientCacheManager {
    constructor(options = {}) {
        this.prefix = options.prefix || 'remmailleuse_cache_';
        this.defaultTTL = options.defaultTTL || 3600000; // 1 heure en ms
        this.maxSize = options.maxSize || 10 * 1024 * 1024; // 10MB
        this.compressionEnabled = options.compression !== false;
        this.debug = options.debug || false;
        
        this.init();
    }
    
    init() {
        // Vérifier le support localStorage
        if (!this.isLocalStorageSupported()) {
            console.warn('LocalStorage non supporté - cache désactivé');
            return;
        }
        
        // Nettoyer les entrées expirées au démarrage
        this.cleanExpired();
        
        // Vérifier la taille du cache
        this.checkCacheSize();
        
        this.log('Cache initialisé');
    }
    
    /**
     * Vérifier le support localStorage
     */
    isLocalStorageSupported() {
        try {
            const testKey = '__test__';
            localStorage.setItem(testKey, 'test');
            localStorage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Générer une clé de cache avec préfixe
     */
    generateKey(key) {
        return this.prefix + key;
    }
    
    /**
     * Compresser les données si possible
     */
    compress(data) {
        if (!this.compressionEnabled) return data;
        
        try {
            // Utiliser la compression native si disponible
            if (typeof CompressionStream !== 'undefined') {
                // Compression moderne (pas encore largement supportée)
                return data;
            }
            
            // Compression simple basée sur JSON
            if (typeof data === 'object') {
                return JSON.stringify(data);
            }
            
            return data;
        } catch (e) {
            this.log('Erreur compression:', e);
            return data;
        }
    }
    
    /**
     * Décompresser les données
     */
    decompress(data) {
        if (!this.compressionEnabled) return data;
        
        try {
            // Tenter de parser comme JSON
            if (typeof data === 'string' && (data.startsWith('{') || data.startsWith('['))) {
                return JSON.parse(data);
            }
            
            return data;
        } catch (e) {
            this.log('Erreur décompression:', e);
            return data;
        }
    }
    
    /**
     * Stocker une valeur dans le cache
     */
    set(key, value, ttl = null) {
        if (!this.isLocalStorageSupported()) return false;
        
        const cacheKey = this.generateKey(key);
        const expires = Date.now() + (ttl || this.defaultTTL);
        
        const cacheData = {
            value: this.compress(value),
            expires,
            created: Date.now(),
            key: key,
            compressed: this.compressionEnabled
        };
        
        try {
            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            this.log('Cache SET:', key, 'TTL:', ttl || this.defaultTTL);
            return true;
        } catch (e) {
            this.log('Erreur cache SET:', e);
            
            // Si erreur de quota, nettoyer le cache
            if (e.name === 'QuotaExceededError') {
                this.clearOldest();
                // Réessayer
                try {
                    localStorage.setItem(cacheKey, JSON.stringify(cacheData));
                    return true;
                } catch (e2) {
                    this.log('Erreur cache SET après nettoyage:', e2);
                    return false;
                }
            }
            
            return false;
        }
    }
    
    /**
     * Récupérer une valeur du cache
     */
    get(key) {
        if (!this.isLocalStorageSupported()) return null;
        
        const cacheKey = this.generateKey(key);
        
        try {
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;
            
            const cacheData = JSON.parse(cached);
            
            // Vérifier l'expiration
            if (Date.now() > cacheData.expires) {
                localStorage.removeItem(cacheKey);
                this.log('Cache EXPIRED:', key);
                return null;
            }
            
            this.log('Cache HIT:', key);
            return cacheData.compressed ? this.decompress(cacheData.value) : cacheData.value;
            
        } catch (e) {
            this.log('Erreur cache GET:', e);
            // Supprimer l'entrée corrompue
            localStorage.removeItem(cacheKey);
            return null;
        }
    }
    
    /**
     * Vérifier si une clé existe dans le cache
     */
    has(key) {
        return this.get(key) !== null;
    }
    
    /**
     * Supprimer une entrée du cache
     */
    delete(key) {
        if (!this.isLocalStorageSupported()) return false;
        
        const cacheKey = this.generateKey(key);
        
        try {
            localStorage.removeItem(cacheKey);
            this.log('Cache DELETE:', key);
            return true;
        } catch (e) {
            this.log('Erreur cache DELETE:', e);
            return false;
        }
    }
    
    /**
     * Vider tout le cache
     */
    clear() {
        if (!this.isLocalStorageSupported()) return 0;
        
        let cleared = 0;
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                localStorage.removeItem(key);
                cleared++;
            }
        });
        
        this.log('Cache CLEAR:', cleared, 'entrées supprimées');
        return cleared;
    }
    
    /**
     * Nettoyer les entrées expirées
     */
    cleanExpired() {
        if (!this.isLocalStorageSupported()) return 0;
        
        let cleaned = 0;
        const keys = Object.keys(localStorage);
        const now = Date.now();
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                try {
                    const cached = localStorage.getItem(key);
                    if (cached) {
                        const cacheData = JSON.parse(cached);
                        if (now > cacheData.expires) {
                            localStorage.removeItem(key);
                            cleaned++;
                        }
                    }
                } catch (e) {
                    // Entrée corrompue, la supprimer
                    localStorage.removeItem(key);
                    cleaned++;
                }
            }
        });
        
        if (cleaned > 0) {
            this.log('Cache CLEAN:', cleaned, 'entrées expirées supprimées');
        }
        
        return cleaned;
    }
    
    /**
     * Vérifier la taille du cache
     */
    checkCacheSize() {
        if (!this.isLocalStorageSupported()) return;
        
        let totalSize = 0;
        const entries = [];
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                const value = localStorage.getItem(key);
                if (value) {
                    const size = new Blob([value]).size;
                    totalSize += size;
                    
                    try {
                        const cacheData = JSON.parse(value);
                        entries.push({
                            key,
                            size,
                            created: cacheData.created,
                            expires: cacheData.expires
                        });
                    } catch (e) {
                        // Entrée corrompue
                        localStorage.removeItem(key);
                    }
                }
            }
        });
        
        this.log('Cache SIZE:', this.formatBytes(totalSize), 'Total entries:', entries.length);
        
        // Si trop volumineux, supprimer les plus anciennes
        if (totalSize > this.maxSize) {
            entries.sort((a, b) => a.created - b.created);
            
            let removed = 0;
            while (totalSize > this.maxSize * 0.8 && entries.length > 0) {
                const oldest = entries.shift();
                localStorage.removeItem(oldest.key);
                totalSize -= oldest.size;
                removed++;
            }
            
            this.log('Cache CLEANUP:', removed, 'entrées anciennes supprimées');
        }
    }
    
    /**
     * Supprimer les entrées les plus anciennes
     */
    clearOldest(count = 10) {
        if (!this.isLocalStorageSupported()) return 0;
        
        const entries = [];
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                const value = localStorage.getItem(key);
                if (value) {
                    try {
                        const cacheData = JSON.parse(value);
                        entries.push({
                            key,
                            created: cacheData.created
                        });
                    } catch (e) {
                        localStorage.removeItem(key);
                    }
                }
            }
        });
        
        // Trier par date de création (plus ancien en premier)
        entries.sort((a, b) => a.created - b.created);
        
        let removed = 0;
        for (let i = 0; i < Math.min(count, entries.length); i++) {
            localStorage.removeItem(entries[i].key);
            removed++;
        }
        
        this.log('Cache CLEAR OLDEST:', removed, 'entrées supprimées');
        return removed;
    }
    
    /**
     * Obtenir les statistiques du cache
     */
    getStats() {
        if (!this.isLocalStorageSupported()) {
            return {
                supported: false,
                entries: 0,
                size: 0,
                sizeFormatted: '0 B'
            };
        }
        
        let totalSize = 0;
        let entries = 0;
        let expired = 0;
        const now = Date.now();
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                const value = localStorage.getItem(key);
                if (value) {
                    totalSize += new Blob([value]).size;
                    entries++;
                    
                    try {
                        const cacheData = JSON.parse(value);
                        if (now > cacheData.expires) {
                            expired++;
                        }
                    } catch (e) {
                        expired++;
                    }
                }
            }
        });
        
        return {
            supported: true,
            entries,
            size: totalSize,
            sizeFormatted: this.formatBytes(totalSize),
            expired,
            maxSize: this.maxSize,
            maxSizeFormatted: this.formatBytes(this.maxSize),
            usagePercent: ((totalSize / this.maxSize) * 100).toFixed(2)
        };
    }
    
    /**
     * Méthode remember - récupère du cache ou exécute le callback
     */
    async remember(key, callback, ttl = null) {
        const cached = this.get(key);
        
        if (cached !== null) {
            this.log('Cache REMEMBER HIT:', key);
            return cached;
        }
        
        this.log('Cache REMEMBER MISS:', key);
        const value = await callback();
        this.set(key, value, ttl);
        
        return value;
    }
    
    /**
     * Cache spécifique pour les requêtes fetch
     */
    async fetchCached(url, options = {}, ttl = null) {
        const cacheKey = 'fetch_' + url + '_' + JSON.stringify(options);
        
        return this.remember(cacheKey, async () => {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            return data;
        }, ttl);
    }
    
    /**
     * Formater les octets en taille lisible
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    /**
     * Logging avec debug
     */
    log(...args) {
        if (this.debug) {
            console.log('[Cache]', ...args);
        }
    }
}

// Instance globale
window.clientCache = new ClientCacheManager({
    prefix: 'remmailleuse_',
    defaultTTL: 1800000, // 30 minutes
    maxSize: 5 * 1024 * 1024, // 5MB
    compression: true,
    debug: false
});

// Nettoyage automatique toutes les 5 minutes
setInterval(() => {
    window.clientCache.cleanExpired();
}, 5 * 60 * 1000);

// Nettoyage au déchargement de la page
window.addEventListener('beforeunload', () => {
    window.clientCache.cleanExpired();
});