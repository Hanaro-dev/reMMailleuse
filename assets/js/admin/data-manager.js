/**
 * Gestionnaire de données spécialisé pour l'interface admin
 * Gère le chargement, la sauvegarde et la validation des données avec authentification
 */

class AdminDataManager {
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
            // Charger les données par défaut en cas d'erreur
            this.data = {
                content: this.getDefaultContent(),
                services: this.getDefaultServices(),
                gallery: this.getDefaultGallery(),
                settings: this.getDefaultSettings()
            };
            return this.data;
        }
    }

    /**
     * Sauvegarde des données
     */
    async saveData(section = null) {
        if (!this.authManager || !this.authManager.isUserAuthenticated()) {
            throw new Error('Utilisateur non authentifié');
        }

        try {
            // En production, on enverrait au serveur
            const response = await this.authManager.authenticatedFetch('../api/admin-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    section: section,
                    data: section ? this.data[section] : this.data
                })
            });

            if (!response) {
                throw new Error('Erreur d\'authentification');
            }

            const result = await response.json();
            
            if (result.success) {
                this.hasUnsavedChanges = false;
                
                // Sauvegarde locale en backup
                localStorage.setItem('remmailleuse-admin-data', JSON.stringify(this.data));
                
                return { success: true, message: result.message || 'Données sauvegardées' };
            } else {
                throw new Error(result.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            
            // Fallback: sauvegarde locale uniquement
            try {
                localStorage.setItem('remmailleuse-admin-data', JSON.stringify(this.data));
                this.hasUnsavedChanges = false;
                return { success: true, message: 'Sauvegardé localement (mode hors-ligne)' };
            } catch (localError) {
                throw new Error('Impossible de sauvegarder les données');
            }
        }
    }

    /**
     * Mise à jour d'une section de données
     */
    updateData(section, key, value) {
        if (!this.data[section]) {
            this.data[section] = {};
        }

        // Validation des données avant mise à jour
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
     * Mise à jour d'une étape de processus
     */
    updateProcessStep(index, field, value) {
        if (!this.data.content.expertise.process[index]) return false;
        
        this.data.content.expertise.process[index][field] = value;
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return true;
    }

    /**
     * Ajout d'une étape de processus
     */
    addProcessStep() {
        const newStep = {
            step: this.data.content.expertise.process.length + 1,
            icon: "🆕",
            title: "Nouvelle étape",
            description: "Description de la nouvelle étape"
        };
        
        this.data.content.expertise.process.push(newStep);
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return newStep;
    }

    /**
     * Suppression d'une étape de processus
     */
    removeProcessStep(index) {
        if (index >= 0 && index < this.data.content.expertise.process.length) {
            this.data.content.expertise.process.splice(index, 1);
            
            // Réorganiser les numéros d'étapes
            this.data.content.expertise.process.forEach((step, i) => {
                step.step = i + 1;
            });
            
            this.hasUnsavedChanges = true;
            this.scheduleAutosave();
            return true;
        }
        return false;
    }

    /**
     * Mise à jour d'un élément de galerie
     */
    updateGalleryItem(index, field, value) {
        if (!this.data.gallery.items[index]) return false;
        
        this.data.gallery.items[index][field] = value;
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return true;
    }

    /**
     * Ajout d'un élément de galerie
     */
    addGalleryItem() {
        const newItem = {
            id: `item-${Date.now()}`,
            category: "tous",
            title: "Nouvelle réalisation",
            description: "Description de la nouvelle réalisation",
            icon: "📷"
        };
        
        this.data.gallery.items.push(newItem);
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return newItem;
    }

    /**
     * Suppression d'un élément de galerie
     */
    removeGalleryItem(index) {
        if (index >= 0 && index < this.data.gallery.items.length) {
            this.data.gallery.items.splice(index, 1);
            this.hasUnsavedChanges = true;
            this.scheduleAutosave();
            return true;
        }
        return false;
    }

    /**
     * Mise à jour d'un service
     */
    updateService(index, field, value) {
        if (!this.data.services.services[index]) return false;
        
        this.data.services.services[index][field] = value;
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return true;
    }

    /**
     * Ajout d'un service
     */
    addService() {
        const newService = {
            id: `service-${Date.now()}`,
            icon: "🆕",
            name: "Nouveau service",
            description: "Description du nouveau service",
            price: "Sur devis"
        };
        
        this.data.services.services.push(newService);
        this.hasUnsavedChanges = true;
        this.scheduleAutosave();
        return newService;
    }

    /**
     * Suppression d'un service
     */
    removeService(index) {
        if (index >= 0 && index < this.data.services.services.length) {
            this.data.services.services.splice(index, 1);
            this.hasUnsavedChanges = true;
            this.scheduleAutosave();
            return true;
        }
        return false;
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
                'hero.title': { type: 'string', required: true, maxLength: 200 },
                'hero.subtitle': { type: 'string', required: true, maxLength: 300 },
                'expertise.intro.name': { type: 'string', required: true, maxLength: 100 },
                'contact.email': { 
                    type: 'string', 
                    required: true, 
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                }
            },
            services: {
                'name': { type: 'string', required: true, maxLength: 100 },
                'description': { type: 'string', required: true, maxLength: 300 },
                'price': { type: 'string', required: true, maxLength: 50 }
            },
            settings: {
                'theme.colors.primary': { type: 'string', pattern: /^#[0-9A-F]{6}$/i },
                'theme.colors.secondary': { type: 'string', pattern: /^#[0-9A-F]{6}$/i },
                'theme.colors.accent': { type: 'string', pattern: /^#[0-9A-F]{6}$/i },
                'theme.colors.neutral': { type: 'string', pattern: /^#[0-9A-F]{6}$/i }
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
                    console.warn('Erreur de sauvegarde automatique:', error);
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
                    console.warn('Erreur de sauvegarde automatique différée:', error);
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
     * Export des données
     */
    exportData() {
        const dataStr = JSON.stringify(this.data, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `remmailleuse-backup-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        
        URL.revokeObjectURL(url);
        return true;
    }

    /**
     * Import des données
     */
    async importData(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const importedData = JSON.parse(e.target.result);
                    
                    // Validation basique de la structure
                    if (this.validateImportedData(importedData)) {
                        this.data = { ...this.data, ...importedData };
                        this.hasUnsavedChanges = true;
                        this.scheduleAutosave();
                        resolve(true);
                    } else {
                        reject(new Error('Structure de données invalide'));
                    }
                } catch (error) {
                    reject(new Error('Fichier JSON invalide'));
                }
            };
            reader.onerror = () => reject(new Error('Erreur de lecture du fichier'));
            reader.readAsText(file);
        });
    }

    /**
     * Validation des données importées
     */
    validateImportedData(data) {
        const requiredSections = ['content', 'services', 'gallery', 'settings'];
        return requiredSections.some(section => data.hasOwnProperty(section));
    }

    /**
     * Données par défaut
     */
    getDefaultContent() {
        return {
            hero: {
                title: "L'art de redonner vie à vos tissus précieux",
                subtitle: "Remaillage traditionnel & réparation invisible depuis plus de 20 ans",
                cta: {
                    text: "Découvrir mon savoir-faire",
                    link: "#expertise"
                }
            },
            expertise: {
                intro: {
                    name: "Mme Monod, Artisane Remmailleuse",
                    description: [
                        "Passionnée par les techniques traditionnelles de remaillage, je redonne vie à vos tissus et lainages les plus précieux. Mon travail consiste à réparer minutieusement chaque maille avec une loupe et un crochet minuscule, remontant maille par maille les lainages endommagés.",
                        "Que ce soit pour refermer un trou de mite avec la plus grande minutie ou effectuer du raccommodage avec ma fidèle machine Elna vintage, j'apporte le plus grand soin à rénover vos tissus à l'identique."
                    ]
                },
                process: [
                    { step: 1, icon: "🔍", title: "Diagnostic", description: "Analyse minutieuse de la pièce pour déterminer la meilleure technique de réparation" },
                    { step: 2, icon: "🧵", title: "Remaillage", description: "Reconstruction maille par maille avec loupe et outils traditionnels" },
                    { step: 3, icon: "✨", title: "Finition", description: "Réparation invisible qui redonne une seconde vie à votre vêtement" }
                ]
            },
            contact: {
                addresses: [
                    { country: "🇨🇭", title: "Suisse", address: "Chemin des Clavins 3", city: "2108 Couvet" },
                    { country: "🇫🇷", title: "France", address: "Poste restante, 17 Rue de Franche Comté", city: "25300 Verrières-de-Joux" }
                ],
                phones: ["+41 32.863.15.31", "+41 79.636.23.22"],
                email: "contact@remmailleuse.com",
                delays: "2 à 5 jours selon réparation"
            }
        };
    }

    getDefaultServices() {
        return {
            services: [
                { id: "remaillage", icon: "🧵", name: "Remaillage classique", description: "Reconstruction maille par maille pour lainages", price: "15-40€" },
                { id: "mite", icon: "🔍", name: "Trous de mite", description: "Réparation invisible minutieuse", price: "20-35€" },
                { id: "bas", icon: "🧦", name: "Bas de contention", description: "Raccommodage machine spécialisée", price: "15-25€" },
                { id: "renovation", icon: "✨", name: "Rénovation tissus", description: "Restauration à l'identique", price: "Sur devis" }
            ]
        };
    }

    getDefaultGallery() {
        return {
            categories: [
                { id: "tous", name: "Tous", active: true },
                { id: "pulls", name: "Pulls" },
                { id: "bas", name: "Bas de contention" },
                { id: "delicats", name: "Tissus délicats" }
            ],
            items: [
                { id: "pull-cachemire", category: "pulls", title: "Pull en cachemire", description: "Réparation invisible d'un trou de mite", icon: "🧥" },
                { id: "bas-contention", category: "bas", title: "Bas de contention", description: "Remaillage précis avec machine Elna", icon: "🧦" },
                { id: "robe-vintage", category: "delicats", title: "Robe vintage", description: "Restauration complète d'une pièce d'époque", icon: "👗" },
                { id: "echarpe-soie", category: "delicats", title: "Écharpe en soie", description: "Réparation délicate de tissus fins", icon: "🧣" }
            ]
        };
    }

    getDefaultSettings() {
        return {
            site: {
                name: "Remmailleuse",
                description: "L'art traditionnel du remaillage pour redonner vie à vos tissus précieux",
                keywords: "remaillage, réparation, tissus, lainages, artisan"
            },
            theme: {
                colors: {
                    primary: "#D4896B",
                    secondary: "#9CAF9A",
                    accent: "#8B6F47",
                    neutral: "#F5F1EB"
                }
            },
            seo: {
                title: "Remmailleuse - Réparation de tissus et lainages",
                description: "Artisane spécialisée en remaillage traditionnel. Réparation invisible de pulls, bas de contention et tissus délicats en Suisse et France."
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
window.AdminDataManager = AdminDataManager;