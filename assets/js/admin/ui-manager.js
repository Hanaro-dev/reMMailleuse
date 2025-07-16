/**
 * Gestionnaire d'interface pour l'interface admin
 * Gère les interactions UI, les formulaires et les notifications
 */

class UIManager {
    constructor(contentManager) {
        this.contentManager = contentManager;
        this.currentSection = 'hero-section';
        this.formElements = new Map();
        this.notifications = [];
        this.setupEventListeners();
    }

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Navigation entre sections
        this.setupSectionNavigation();
        
        // Gestion des formulaires
        this.setupFormHandlers();
        
        // Gestion des notifications
        this.setupNotificationSystem();
        
        // Gestion des raccourcis clavier
        this.setupKeyboardShortcuts();
        
        // Gestion de la confirmation avant fermeture
        this.setupBeforeUnloadHandler();
    }

    /**
     * Navigation entre sections
     */
    setupSectionNavigation() {
        const navButtons = document.querySelectorAll('.nav-btn');
        const sections = document.querySelectorAll('.admin-section');

        navButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetSection = button.dataset.section;
                this.switchSection(targetSection);
            });
        });
    }

    /**
     * Basculement entre sections
     */
    switchSection(sectionId) {
        // Masquer toutes les sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });

        // Afficher la section cible
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = sectionId;
            
            // Mettre à jour la navigation
            this.updateNavigation(sectionId);
            
            // Populer les champs de la section
            this.populateSection(sectionId);
        }
    }

    /**
     * Mise à jour de la navigation active
     */
    updateNavigation(activeSectionId) {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.section === activeSectionId) {
                btn.classList.add('active');
            }
        });
    }

    /**
     * Population des champs d'une section
     */
    populateSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (!section) return;

        const inputs = section.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            const fieldPath = input.dataset.field;
            if (fieldPath) {
                const value = this.getFieldValue(fieldPath);
                if (value !== null) {
                    input.value = value;
                }
            }
        });
    }

    /**
     * Récupération de la valeur d'un champ
     */
    getFieldValue(fieldPath) {
        const parts = fieldPath.split('.');
        let current = this.contentManager.getData();
        
        for (const part of parts) {
            if (current && typeof current === 'object') {
                current = current[part];
            } else {
                return null;
            }
        }
        
        return current;
    }

    /**
     * Gestion des formulaires
     */
    setupFormHandlers() {
        // Gestion des changements dans les champs
        document.addEventListener('input', (e) => {
            if (e.target.dataset.field) {
                this.handleFieldChange(e.target);
            }
        });

        // Gestion des soumissions de formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('admin-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });

        // Boutons de sauvegarde
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveCurrentSection();
            });
        });
    }

    /**
     * Gestion des changements de champs
     */
    handleFieldChange(field) {
        const fieldPath = field.dataset.field;
        const value = field.value;
        
        try {
            // Mise à jour des données
            this.updateFieldValue(fieldPath, value);
            
            // Indication visuelle des changements
            this.markFieldAsChanged(field);
            
            // Mise à jour du statut
            this.updateSaveStatus();
            
        } catch (error) {
            console.error('Erreur lors de la mise à jour du champ:', error);
            this.showNotification('Erreur lors de la mise à jour du champ', 'error');
        }
    }

    /**
     * Mise à jour de la valeur d'un champ
     */
    updateFieldValue(fieldPath, value) {
        const parts = fieldPath.split('.');
        const section = parts[0];
        const keyPath = parts.slice(1).join('.');
        
        if (keyPath.includes('.')) {
            // Gestion des chemins imbriqués
            const keys = keyPath.split('.');
            let current = this.contentManager.getData(section);
            
            for (let i = 0; i < keys.length - 1; i++) {
                if (!current[keys[i]]) {
                    current[keys[i]] = {};
                }
                current = current[keys[i]];
            }
            
            current[keys[keys.length - 1]] = value;
            this.contentManager.updateData(section, current);
        } else {
            this.contentManager.updateData(section, keyPath, value);
        }
    }

    /**
     * Indication visuelle des changements
     */
    markFieldAsChanged(field) {
        field.classList.add('changed');
        
        // Supprimer l'indication après 2 secondes
        setTimeout(() => {
            field.classList.remove('changed');
        }, 2000);
    }

    /**
     * Gestion des soumissions de formulaires
     */
    async handleFormSubmit(form) {
        try {
            const section = form.dataset.section;
            await this.contentManager.saveData(section);
            this.showNotification('Données sauvegardées avec succès', 'success');
            this.updateSaveStatus();
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            this.showNotification('Erreur lors de la sauvegarde', 'error');
        }
    }

    /**
     * Sauvegarde de la section courante
     */
    async saveCurrentSection() {
        try {
            await this.contentManager.saveData();
            this.showNotification('Données sauvegardées avec succès', 'success');
            this.updateSaveStatus();
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            this.showNotification('Erreur lors de la sauvegarde', 'error');
        }
    }

    /**
     * Mise à jour du statut de sauvegarde
     */
    updateSaveStatus() {
        const statusElement = document.querySelector('.save-status');
        if (statusElement) {
            if (this.contentManager.hasUnsavedData()) {
                statusElement.textContent = 'Modifications non sauvegardées';
                statusElement.className = 'save-status unsaved';
            } else {
                statusElement.textContent = 'Tout est sauvegardé';
                statusElement.className = 'save-status saved';
            }
        }
    }

    /**
     * Système de notifications
     */
    setupNotificationSystem() {
        // Créer le conteneur de notifications s'il n'existe pas
        if (!document.querySelector('.notifications-container')) {
            const container = document.createElement('div');
            container.className = 'notifications-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Affichage des notifications
     */
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icon = this.getNotificationIcon(type);
        const iconSpan = document.createElement('span');
        iconSpan.className = 'notification-icon';
        iconSpan.textContent = icon;
        
        const messageSpan = document.createElement('span');
        messageSpan.className = 'notification-message';
        messageSpan.textContent = message;
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'notification-close';
        closeBtn.textContent = '×';
        closeBtn.onclick = () => notification.remove();
        
        notification.appendChild(iconSpan);
        notification.appendChild(messageSpan);
        notification.appendChild(closeBtn);

        container.appendChild(notification);

        // Animation d'apparition
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Suppression automatique
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.parentElement.removeChild(notification);
                }
            }, 300);
        }, duration);
    }

    /**
     * Icônes de notification
     */
    getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    /**
     * Raccourcis clavier
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+S pour sauvegarder
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.saveCurrentSection();
            }
            
            // Ctrl+1-9 pour changer de section
            if (e.ctrlKey && e.key >= '1' && e.key <= '9') {
                e.preventDefault();
                const sectionIndex = parseInt(e.key) - 1;
                const sections = document.querySelectorAll('.nav-btn');
                if (sections[sectionIndex]) {
                    const sectionId = sections[sectionIndex].dataset.section;
                    this.switchSection(sectionId);
                }
            }
        });
    }

    /**
     * Gestion avant fermeture
     */
    setupBeforeUnloadHandler() {
        window.addEventListener('beforeunload', (e) => {
            if (this.contentManager.hasUnsavedData()) {
                e.preventDefault();
                e.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
                return e.returnValue;
            }
        });
    }

    /**
     * Affichage/masquage du chargement
     */
    showLoading() {
        const loadingElement = document.querySelector('.loading-overlay');
        if (loadingElement) {
            loadingElement.style.display = 'flex';
        }
    }

    hideLoading() {
        const loadingElement = document.querySelector('.loading-overlay');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    /**
     * Gestion des erreurs UI
     */
    handleError(error, context = 'Erreur') {
        console.error(`${context}:`, error);
        this.showNotification(`${context}: ${error.message}`, 'error');
    }

    /**
     * Validation des formulaires
     */
    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });

        return isValid;
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        // Nettoyer les événements et timers
        this.notifications = [];
    }
}

// Export pour utilisation dans d'autres modules
window.UIManager = UIManager;