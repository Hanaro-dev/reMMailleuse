/**
 * Gestionnaire de contenu pour l'interface admin
 * Gère le chargement, la sauvegarde et la validation du contenu
 */

class ContentManager {
    constructor(authManager) {
        this.authManager = authManager;
        this.data = {};
        this.hasUnsavedChanges = false;
        this.autosaveInterval = null;
        this.autosaveDelay = 30000; // 30 secondes
        this.validationRules = this.getValidationRules();
    }

    /**
     * Chargement de toutes les données
     */
    async loadData() {
        try {
            const [contentResponse, servicesResponse, galleryResponse, settingsResponse] = await Promise.all([
                fetch('../data/content.json').catch(() => ({ ok: false })),
                fetch('../data/services.json').catch(() => ({ ok: false })),
                fetch('../data/gallery.json').catch(() => ({ ok: false })),
                fetch('../data/settings.json').catch(() => ({ ok: false }))
            ]);

            this.data = {
                content: contentResponse.ok ? await contentResponse.json() : this.getDefaultContent(),
                services: servicesResponse.ok ? await servicesResponse.json() : this.getDefaultServices(),
                gallery: galleryResponse.ok ? await galleryResponse.json() : this.getDefaultGallery(),
                settings: settingsResponse.ok ? await settingsResponse.json() : this.getDefaultSettings()
            };

            return this.data;
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            throw new Error('Impossible de charger les données du site');
        }
    }

    /**
     * Sauvegarde des données
     */
    async saveData(section = null) {
        if (!this.authManager.isUserAuthenticated()) {
            throw new Error('Utilisateur non authentifié');
        }

        try {
            const dataToSave = section ? { [section]: this.data[section] } : this.data;
            
            const response = await this.authManager.authenticatedFetch('../api/admin-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    data: dataToSave
                })
            });

            if (!response) {
                throw new Error('Erreur d\'authentification');
            }

            const result = await response.json();
            
            if (result.success) {
                this.hasUnsavedChanges = false;
                return { success: true, message: result.message };
            } else {
                throw new Error(result.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            throw error;
        }
    }

    /**
     * Mise à jour d'une section de données
     */
    updateData(section, key, value) {
        if (!this.data[section]) {
            this.data[section] = {};
        }

        // Validation des données
        if (!this.validateData(section, key, value)) {
            throw new Error(`Données invalides pour ${section}.${key}`);
        }

        // Mise à jour des données
        if (typeof key === 'object') {
            this.data[section] = { ...this.data[section], ...key };
        } else {
            this.data[section][key] = value;
        }

        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        
        return true;
    }

    /**
     * Récupération de données
     */
    getData(section = null, key = null) {
        if (!section) {
            return this.data;
        }

        if (!key) {
            return this.data[section] || {};
        }

        return this.data[section] ? this.data[section][key] : null;
    }

    /**
     * Validation des données
     */
    validateData(section, key, value) {
        const rules = this.validationRules[section];
        if (!rules || !rules[key]) {
            return true; // Pas de règle = validation OK
        }

        const rule = rules[key];
        
        // Validation du type
        if (rule.type && typeof value !== rule.type) {
            return false;
        }

        // Validation de la longueur
        if (rule.maxLength && value.length > rule.maxLength) {
            return false;
        }

        if (rule.minLength && value.length < rule.minLength) {
            return false;
        }

        // Validation des valeurs requises
        if (rule.required && (!value || value.trim() === '')) {
            return false;
        }

        // Validation par regex
        if (rule.pattern && !rule.pattern.test(value)) {
            return false;
        }

        return true;
    }

    /**
     * Règles de validation
     */
    getValidationRules() {
        return {
            content: {
                'hero.title': { type: 'string', required: true, maxLength: 100 },
                'hero.subtitle': { type: 'string', required: true, maxLength: 200 },
                'expertise.intro.name': { type: 'string', required: true, maxLength: 100 },
                'contact.email': { 
                    type: 'string', 
                    required: true, 
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                }
            },
            services: {
                'services.*.name': { type: 'string', required: true, maxLength: 50 },
                'services.*.description': { type: 'string', required: true, maxLength: 200 },
                'services.*.price': { type: 'string', required: true, maxLength: 20 }
            },
            settings: {
                'theme.colors.primary': { type: 'string', pattern: /^#[0-9A-F]{6}$/i },
                'theme.colors.secondary': { type: 'string', pattern: /^#[0-9A-F]{6}$/i }
            }
        };
    }

    /**
     * Configuration de la sauvegarde automatique
     */
    setupAutosave() {
        this.clearAutosave();
        
        this.autosaveInterval = setInterval(async () => {
            if (this.hasUnsavedChanges) {
                try {
                    await this.saveData();
                    console.log('Sauvegarde automatique effectuée');
                } catch (error) {
                    console.error('Erreur de sauvegarde automatique:', error);
                }
            }
        }, this.autosaveDelay);
    }

    /**
     * Programmation de la sauvegarde automatique
     */
    scheduleAutosave() {
        // Déclencher la sauvegarde après 5 secondes d'inactivité
        clearTimeout(this.autosaveTimeout);
        this.autosaveTimeout = setTimeout(async () => {
            if (this.hasUnsavedChanges) {
                try {
                    await this.saveData();
                } catch (error) {
                    console.error('Erreur de sauvegarde automatique:', error);
                }
            }
        }, 5000);
    }

    /**
     * Nettoyage de la sauvegarde automatique
     */
    clearAutosave() {
        if (this.autosaveInterval) {
            clearInterval(this.autosaveInterval);
            this.autosaveInterval = null;
        }
        
        if (this.autosaveTimeout) {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = null;
        }
    }

    /**
     * Données par défaut
     */
    getDefaultContent() {
        return {
            site: {
                name: "Remmailleuse",
                tagline: "L'art de redonner vie à vos tissus précieux"
            },
            hero: {
                title: "L'art de redonner vie à vos tissus précieux",
                subtitle: "Remaillage traditionnel & réparation invisible depuis plus de 20 ans",
                cta: { text: "Découvrir mon savoir-faire", link: "#expertise" }
            },
            expertise: {
                title: "Mon Expertise",
                intro: {
                    name: "Mme Monod, Artisane Remmailleuse",
                    description: "Passionnée par les techniques traditionnelles de remaillage, je redonne vie à vos tissus et lainages les plus précieux."
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
        };
    }

    getDefaultServices() {
        return {
            services: [
                {
                    id: "remaillage",
                    icon: "🧵",
                    name: "Remaillage classique",
                    description: "Reconstruction maille par maille pour lainages",
                    price: "15-40€"
                }
            ]
        };
    }

    getDefaultGallery() {
        return {
            categories: [
                { id: "tous", name: "Tous", active: true }
            ],
            items: []
        };
    }

    getDefaultSettings() {
        return {
            theme: {
                colors: {
                    primary: "#D4896B",
                    secondary: "#9CAF9A"
                }
            }
        };
    }

    /**
     * Vérification des changements non sauvegardés
     */
    hasUnsavedData() {
        return this.hasUnsavedChanges;
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        this.clearAutosave();
        this.hasUnsavedChanges = false;
    }
}

// Export pour utilisation dans d'autres modules
window.ContentManager = ContentManager;