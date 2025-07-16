/**
 * Gestionnaire de formulaires pour l'interface admin
 * Gère la population des champs, la validation et la collecte des données
 */

class AdminFormManager {
    constructor(dataManager) {
        this.dataManager = dataManager;
        this.formElements = new Map();
        this.validators = new Map();
        this.setupEventListeners();
    }

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Gestion des changements dans les formulaires
        document.addEventListener('input', (e) => {
            if (e.target.matches('.form-input')) {
                this.handleFieldChange(e.target);
            }
        });

        // Validation en temps réel
        document.addEventListener('blur', (e) => {
            if (e.target.matches('.form-input')) {
                this.validateField(e.target);
            }
        }, true);

        // Gestion des formulaires de soumission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('admin-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });
    }

    /**
     * Population de tous les champs
     */
    populateAllFields() {
        this.populateHeroFields();
        this.populateExpertiseFields();
        this.populateContactFields();
        this.populateSettingsFields();
    }

    /**
     * Population des champs Hero
     */
    populateHeroFields() {
        const hero = this.dataManager.getData('content', 'hero');
        if (!hero) return;

        this.setFieldValue('hero-title', hero.title);
        this.setFieldValue('hero-subtitle', hero.subtitle);
        this.setFieldValue('hero-cta-text', hero.cta?.text);
        this.setFieldValue('hero-cta-link', hero.cta?.link);
    }

    /**
     * Population des champs Expertise
     */
    populateExpertiseFields() {
        const expertise = this.dataManager.getData('content', 'expertise');
        if (!expertise) return;

        this.setFieldValue('expert-name', expertise.intro?.name);
        if (expertise.intro?.description) {
            this.setFieldValue('expert-desc-1', expertise.intro.description[0] || '');
            this.setFieldValue('expert-desc-2', expertise.intro.description[1] || '');
        }
    }

    /**
     * Population des champs Contact
     */
    populateContactFields() {
        const contact = this.dataManager.getData('content', 'contact');
        if (!contact) return;
        
        if (contact.addresses && contact.addresses[0]) {
            this.setFieldValue('address-ch', contact.addresses[0].address);
            this.setFieldValue('city-ch', contact.addresses[0].city);
        }
        
        if (contact.addresses && contact.addresses[1]) {
            this.setFieldValue('address-fr', contact.addresses[1].address);
            this.setFieldValue('city-fr', contact.addresses[1].city);
        }
        
        if (contact.phones) {
            this.setFieldValue('phone-1', contact.phones[0] || '');
            this.setFieldValue('phone-2', contact.phones[1] || '');
        }
        
        this.setFieldValue('contact-email', contact.email);
        this.setFieldValue('repair-delays', contact.delays);
    }

    /**
     * Population des champs Settings
     */
    populateSettingsFields() {
        const settings = this.dataManager.getData('settings');
        if (!settings) return;

        this.setFieldValue('site-name', settings.site?.name || '');
        this.setFieldValue('site-description', settings.seo?.description || '');
        this.setFieldValue('site-keywords', settings.seo?.keywords || '');
        
        if (settings.theme?.colors) {
            this.setFieldValue('color-primary', settings.theme.colors.primary);
            this.setFieldValue('color-secondary', settings.theme.colors.secondary);
            this.setFieldValue('color-accent', settings.theme.colors.accent);
            this.setFieldValue('color-neutral', settings.theme.colors.neutral);
        }
    }

    /**
     * Définition de la valeur d'un champ
     */
    setFieldValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (field && value !== undefined && value !== null) {
            field.value = value;
            // Marquer le champ comme initialisé
            field.dataset.initialized = 'true';
        }
    }

    /**
     * Récupération de la valeur d'un champ
     */
    getFieldValue(fieldId) {
        const field = document.getElementById(fieldId);
        return field ? field.value.trim() : '';
    }

    /**
     * Gestion des changements de champs
     */
    handleFieldChange(field) {
        const fieldId = field.id;
        const value = field.value;

        // Ne pas traiter les champs non initialisés
        if (!field.dataset.initialized) return;

        // Mise à jour des données selon le type de champ
        this.updateDataFromField(fieldId, value);
        
        // Indication visuelle du changement
        this.markFieldAsChanged(field);
        
        // Validation en temps réel pour certains champs
        if (field.type === 'email' || field.type === 'tel' || field.type === 'url') {
            this.validateField(field);
        }
    }

    /**
     * Mise à jour des données depuis un champ
     */
    updateDataFromField(fieldId, value) {
        try {
            switch (fieldId) {
                // Hero
                case 'hero-title':
                    this.dataManager.updateData('content', 'hero', { 
                        ...this.dataManager.getData('content', 'hero'), 
                        title: value 
                    });
                    break;
                case 'hero-subtitle':
                    this.dataManager.updateData('content', 'hero', { 
                        ...this.dataManager.getData('content', 'hero'), 
                        subtitle: value 
                    });
                    break;
                case 'hero-cta-text':
                    const heroData = this.dataManager.getData('content', 'hero');
                    this.dataManager.updateData('content', 'hero', { 
                        ...heroData, 
                        cta: { ...heroData.cta, text: value }
                    });
                    break;
                case 'hero-cta-link':
                    const heroDataLink = this.dataManager.getData('content', 'hero');
                    this.dataManager.updateData('content', 'hero', { 
                        ...heroDataLink, 
                        cta: { ...heroDataLink.cta, link: value }
                    });
                    break;

                // Expertise
                case 'expert-name':
                    const expertiseData = this.dataManager.getData('content', 'expertise');
                    this.dataManager.updateData('content', 'expertise', {
                        ...expertiseData,
                        intro: { ...expertiseData.intro, name: value }
                    });
                    break;
                case 'expert-desc-1':
                case 'expert-desc-2':
                    this.updateExpertiseDescription();
                    break;

                // Contact
                case 'address-ch':
                case 'city-ch':
                case 'address-fr':
                case 'city-fr':
                case 'phone-1':
                case 'phone-2':
                case 'contact-email':
                case 'repair-delays':
                    this.updateContactData();
                    break;

                // Settings
                case 'site-name':
                    const siteData = this.dataManager.getData('settings', 'site') || {};
                    this.dataManager.updateData('settings', 'site', { ...siteData, name: value });
                    break;
                case 'site-description':
                    const seoData = this.dataManager.getData('settings', 'seo') || {};
                    this.dataManager.updateData('settings', 'seo', { ...seoData, description: value });
                    break;
                case 'site-keywords':
                    const seoKeywords = this.dataManager.getData('settings', 'seo') || {};
                    this.dataManager.updateData('settings', 'seo', { ...seoKeywords, keywords: value });
                    break;

                // Couleurs
                case 'color-primary':
                case 'color-secondary':
                case 'color-accent':
                case 'color-neutral':
                    this.updateThemeColors();
                    break;
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour des données:', error);
        }
    }

    /**
     * Mise à jour de la description d'expertise
     */
    updateExpertiseDescription() {
        const desc1 = this.getFieldValue('expert-desc-1');
        const desc2 = this.getFieldValue('expert-desc-2');
        const descriptions = [desc1, desc2].filter(desc => desc.trim());

        const expertiseData = this.dataManager.getData('content', 'expertise');
        this.dataManager.updateData('content', 'expertise', {
            ...expertiseData,
            intro: { 
                ...expertiseData.intro, 
                description: descriptions 
            }
        });
    }

    /**
     * Mise à jour des données de contact
     */
    updateContactData() {
        const contactData = {
            addresses: [
                {
                    country: "🇨🇭",
                    title: "Suisse",
                    address: this.getFieldValue('address-ch'),
                    city: this.getFieldValue('city-ch')
                },
                {
                    country: "🇫🇷",
                    title: "France", 
                    address: this.getFieldValue('address-fr'),
                    city: this.getFieldValue('city-fr')
                }
            ],
            phones: [
                this.getFieldValue('phone-1'),
                this.getFieldValue('phone-2')
            ].filter(phone => phone.trim()),
            email: this.getFieldValue('contact-email'),
            delays: this.getFieldValue('repair-delays')
        };

        this.dataManager.updateData('content', 'contact', contactData);
    }

    /**
     * Mise à jour des couleurs du thème
     */
    updateThemeColors() {
        const colors = {
            primary: this.getFieldValue('color-primary'),
            secondary: this.getFieldValue('color-secondary'),
            accent: this.getFieldValue('color-accent'),
            neutral: this.getFieldValue('color-neutral')
        };

        const themeData = this.dataManager.getData('settings', 'theme') || {};
        this.dataManager.updateData('settings', 'theme', {
            ...themeData,
            colors: colors
        });
    }

    /**
     * Collecte de toutes les données des formulaires
     */
    collectFormData() {
        // Cette méthode force la collecte de toutes les données des formulaires
        this.updateContactData();
        this.updateExpertiseDescription();
        this.updateThemeColors();
        
        // Mise à jour des autres champs simples
        const fields = document.querySelectorAll('.form-input[data-initialized="true"]');
        fields.forEach(field => {
            if (field.value !== field.defaultValue) {
                this.updateDataFromField(field.id, field.value);
            }
        });
    }

    /**
     * Indication visuelle des changements
     */
    markFieldAsChanged(field) {
        field.classList.add('changed');
        
        // Ajouter un indicateur à la section
        const section = field.closest('.admin-section');
        if (section) {
            section.classList.add('has-changes');
        }
        
        // Supprimer l'indication après 2 secondes
        setTimeout(() => {
            field.classList.remove('changed');
        }, 2000);
    }

    /**
     * Validation d'un champ
     */
    validateField(field) {
        const fieldType = field.type;
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Validation selon le type
        switch (fieldType) {
            case 'email':
                isValid = this.validateEmail(value);
                message = isValid ? '' : 'Format d\'email invalide';
                break;
            case 'tel':
                isValid = this.validatePhone(value);
                message = isValid ? '' : 'Format de téléphone invalide';
                break;
            case 'url':
                isValid = this.validateUrl(value);
                message = isValid ? '' : 'URL invalide';
                break;
        }

        // Validation des champs requis
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Ce champ est obligatoire';
        }

        // Affichage du résultat de validation
        this.displayValidationResult(field, isValid, message);
        
        return isValid;
    }

    /**
     * Validation d'email
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validation de téléphone
     */
    validatePhone(phone) {
        const phoneRegex = /^\+?[\d\s\.\-\(\)]{8,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Validation d'URL
     */
    validateUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Affichage du résultat de validation
     */
    displayValidationResult(field, isValid, message) {
        // Couleur de bordure
        field.style.borderColor = isValid ? 'var(--color-success, #28a745)' : 'var(--color-error, #dc3545)';
        
        // Message de feedback
        let feedback = field.parentNode.querySelector('.form-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'form-feedback';
            field.parentNode.appendChild(feedback);
        }
        
        feedback.textContent = message;
        feedback.className = `form-feedback ${isValid ? 'form-success' : 'form-error'}`;
        feedback.style.display = message ? 'block' : 'none';
    }

    /**
     * Validation d'un formulaire complet
     */
    validateForm(form) {
        const fields = form.querySelectorAll('.form-input');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Gestion de la soumission de formulaire
     */
    async handleFormSubmit(form) {
        if (!this.validateForm(form)) {
            return false;
        }

        try {
            // Collecter les données du formulaire
            this.collectFormData();
            
            // Sauvegarder
            const section = form.dataset.section;
            await this.dataManager.saveData(section);
            
            return true;
        } catch (error) {
            console.error('Erreur lors de la soumission du formulaire:', error);
            throw error;
        }
    }

    /**
     * Réinitialisation d'un formulaire
     */
    resetForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const fields = form.querySelectorAll('.form-input');
        fields.forEach(field => {
            field.value = field.defaultValue || '';
            field.style.borderColor = '';
            field.classList.remove('changed', 'error');
            
            // Supprimer les messages de feedback
            const feedback = field.parentNode.querySelector('.form-feedback');
            if (feedback) {
                feedback.remove();
            }
        });

        // Repopuler avec les données actuelles
        this.populateAllFields();
    }

    /**
     * Aperçu des changements de couleurs
     */
    previewColorChange(input) {
        const colorName = input.id.replace('color-', '');
        const value = input.value;
        
        if (this.validateColor(value)) {
            document.documentElement.style.setProperty(`--color-${colorName}`, value);
        }
    }

    /**
     * Validation de couleur
     */
    validateColor(color) {
        const s = new Option().style;
        s.color = color;
        return s.color !== '';
    }

    /**
     * Application de l'aperçu du thème
     */
    applyThemePreview() {
        const colors = {
            primary: this.getFieldValue('color-primary'),
            secondary: this.getFieldValue('color-secondary'),
            accent: this.getFieldValue('color-accent'),
            neutral: this.getFieldValue('color-neutral')
        };

        Object.entries(colors).forEach(([name, value]) => {
            if (value && this.validateColor(value)) {
                document.documentElement.style.setProperty(`--color-${name}`, value);
            }
        });

        return true;
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        this.formElements.clear();
        this.validators.clear();
    }
}

// Export pour utilisation dans d'autres modules
window.AdminFormManager = AdminFormManager;