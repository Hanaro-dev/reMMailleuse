/**
 * Gestionnaire de données pour le site ReMmailleuse
 * Centralise le chargement et la gestion des données JSON
 */

class DataManager {
    constructor() {
        this.data = {};
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
    }

    /**
     * Charge toutes les données nécessaires au site
     */
    async loadAllData() {
        try {
            const [contentResponse, servicesResponse, galleryResponse, settingsResponse] = await Promise.all([
                this.fetchWithCache('data/content.json'),
                this.fetchWithCache('data/services.json'),
                this.fetchWithCache('data/gallery.json'),
                this.fetchWithCache('data/settings.json')
            ]);

            this.data = {
                content: contentResponse,
                services: servicesResponse,
                gallery: galleryResponse,
                settings: settingsResponse
            };

            return this.data;
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            throw new Error('Impossible de charger les données du site');
        }
    }

    /**
     * Fetch avec mise en cache
     */
    async fetchWithCache(url) {
        const cacheKey = url;
        const cached = this.cache.get(cacheKey);
        
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            return cached.data;
        }

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status} pour ${url}`);
        }

        const data = await response.json();
        this.cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });

        return data;
    }

    /**
     * Récupère une section spécifique des données
     */
    get(section, key = null) {
        if (!this.data[section]) {
            return null;
        }

        if (key) {
            return this.data[section][key] || null;
        }

        return this.data[section];
    }

    /**
     * Met à jour une section des données
     */
    set(section, key, value) {
        if (!this.data[section]) {
            this.data[section] = {};
        }

        if (typeof key === 'object') {
            this.data[section] = key;
        } else {
            this.data[section][key] = value;
        }
    }

    /**
     * Vide le cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Récupère les données par défaut en cas d'erreur
     */
    getDefaultData() {
        return {
            content: {
                site: {
                    name: "Remmailleuse",
                    tagline: "L'art de redonner vie à vos tissus précieux"
                },
                hero: {
                    title: "L'art de redonner vie à vos tissus précieux",
                    subtitle: "Remaillage traditionnel & réparation invisible",
                    cta: { text: "Découvrir mon savoir-faire", link: "#expertise" }
                },
                expertise: {
                    title: "Mon Expertise",
                    intro: {
                        name: "Mme Monod, Artisane Remmailleuse",
                        description: "Passionnée par les techniques traditionnelles de remaillage."
                    },
                    process: {
                        steps: [
                            { step: 1, icon: "🔍", title: "Diagnostic", description: "Analyse minutieuse de la pièce" },
                            { step: 2, icon: "🧵", title: "Remaillage", description: "Reconstruction maille par maille" },
                            { step: 3, icon: "✨", title: "Finition", description: "Réparation invisible" }
                        ]
                    }
                },
                contact: {
                    addresses: [
                        { country: "🇨🇭", title: "Suisse", address: "Chemin des Clavins 3", city: "2108 Couvet" }
                    ],
                    phones: ["+41 32.863.15.31"],
                    email: "contact@remmailleuse.com"
                }
            },
            services: {
                services: [
                    { id: "remaillage", icon: "🧵", name: "Remaillage classique", description: "Reconstruction maille par maille", price: "15-40€" }
                ]
            },
            gallery: {
                categories: [
                    { id: "tous", name: "Tous", active: true }
                ],
                items: []
            },
            settings: {
                theme: {
                    colors: {
                        primary: "#D4896B",
                        secondary: "#9CAF9A"
                    }
                }
            }
        };
    }
}

// Export global
window.DataManager = DataManager;