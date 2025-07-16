/**
 * Utilitaires pour l'interface admin
 * Fonctions d'aide, validation et gestion UI
 */

class AdminUtilities {
    constructor() {
        this.setupConstants();
    }

    /**
     * Configuration des constantes
     */
    setupConstants() {
        this.validationRules = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^\+?[\d\s\.\-\(\)]{8,}$/,
            url: /^https?:\/\/.+/,
            color: /^#[0-9A-F]{6}$/i
        };

        this.messageIcons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
    }

    /**
     * Gestion des modales de confirmation
     */
    showConfirmModal(title, message, onConfirm, onCancel = null) {
        // Créer la modale si elle n'existe pas
        let modal = document.getElementById('admin-confirm-modal');
        if (!modal) {
            modal = this.createConfirmModal();
        }

        // Mettre à jour le contenu
        const titleEl = modal.querySelector('.modal-title');
        const messageEl = modal.querySelector('.modal-message');
        const confirmBtn = modal.querySelector('.modal-confirm');
        const cancelBtn = modal.querySelector('.modal-cancel');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;

        // Gérer les événements
        confirmBtn.onclick = () => {
            this.hideConfirmModal();
            if (onConfirm) onConfirm();
        };

        cancelBtn.onclick = () => {
            this.hideConfirmModal();
            if (onCancel) onCancel();
        };

        // Afficher la modale
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);

        return modal;
    }

    /**
     * Création de la modale de confirmation
     */
    createConfirmModal() {
        const modal = document.createElement('div');
        modal.id = 'admin-confirm-modal';
        modal.className = 'admin-modal';
        modal.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <h3 class="modal-title"></h3>
                <p class="modal-message"></p>
                <div class="modal-actions">
                    <button class="btn btn-outline modal-cancel">Annuler</button>
                    <button class="btn btn-primary modal-confirm">Confirmer</button>
                </div>
            </div>
        `;

        // Fermeture par clic sur l'overlay
        modal.querySelector('.modal-overlay').onclick = () => this.hideConfirmModal();

        document.body.appendChild(modal);
        return modal;
    }

    /**
     * Masquage de la modale de confirmation
     */
    hideConfirmModal() {
        const modal = document.getElementById('admin-confirm-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    /**
     * Gestion du chargement
     */
    showLoading(message = 'Chargement...') {
        let loader = document.getElementById('admin-loader');
        if (!loader) {
            loader = this.createLoader();
        }

        const messageEl = loader.querySelector('.loader-message');
        if (messageEl) messageEl.textContent = message;

        loader.style.display = 'flex';
        setTimeout(() => loader.classList.add('show'), 10);

        return loader;
    }

    /**
     * Création du loader
     */
    createLoader() {
        const loader = document.createElement('div');
        loader.id = 'admin-loader';
        loader.className = 'admin-loader';
        loader.innerHTML = `
            <div class="loader-overlay"></div>
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <p class="loader-message">Chargement...</p>
            </div>
        `;

        document.body.appendChild(loader);
        return loader;
    }

    /**
     * Masquage du chargement
     */
    hideLoading() {
        const loader = document.getElementById('admin-loader');
        if (loader) {
            loader.classList.remove('show');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
    }

    /**
     * Gestion des notifications toast
     */
    showToast(message, type = 'info', duration = 5000) {
        const toast = this.createToast(message, type);
        document.body.appendChild(toast);

        // Animation d'apparition
        setTimeout(() => toast.classList.add('show'), 100);

        // Suppression automatique
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        }, duration);

        return toast;
    }

    /**
     * Création d'un toast
     */
    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `admin-toast ${type}`;
        
        const icon = this.messageIcons[type] || this.messageIcons.info;
        
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${this.escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;

        return toast;
    }

    /**
     * Validation des données
     */
    validateField(value, type, required = false) {
        // Vérification des champs requis
        if (required && (!value || value.trim() === '')) {
            return { valid: false, message: 'Ce champ est obligatoire' };
        }

        // Si vide et non requis, c'est valide
        if (!value || value.trim() === '') {
            return { valid: true, message: '' };
        }

        // Validation selon le type
        switch (type) {
            case 'email':
                return {
                    valid: this.validationRules.email.test(value),
                    message: 'Format d\'email invalide'
                };
            
            case 'phone':
                return {
                    valid: this.validationRules.phone.test(value),
                    message: 'Format de téléphone invalide'
                };
            
            case 'url':
                return {
                    valid: this.validationRules.url.test(value),
                    message: 'URL invalide'
                };
            
            case 'color':
                return {
                    valid: this.validationRules.color.test(value),
                    message: 'Format de couleur invalide (ex: #FF0000)'
                };
            
            case 'text':
                return {
                    valid: value.length <= 500,
                    message: 'Texte trop long (max 500 caractères)'
                };
            
            default:
                return { valid: true, message: '' };
        }
    }

    /**
     * Validation d'un formulaire complet
     */
    validateForm(formElement) {
        const fields = formElement.querySelectorAll('input, textarea, select');
        const errors = [];
        let isValid = true;

        fields.forEach(field => {
            const fieldType = field.dataset.validationType || field.type;
            const isRequired = field.hasAttribute('required');
            const validation = this.validateField(field.value, fieldType, isRequired);

            if (!validation.valid) {
                isValid = false;
                errors.push({
                    field: field,
                    message: validation.message
                });
                
                // Marquer le champ en erreur
                field.classList.add('error');
                this.showFieldError(field, validation.message);
            } else {
                field.classList.remove('error');
                this.hideFieldError(field);
            }
        });

        return {
            valid: isValid,
            errors: errors
        };
    }

    /**
     * Affichage d'erreur sur un champ
     */
    showFieldError(field, message) {
        let errorEl = field.parentNode.querySelector('.field-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error';
            field.parentNode.appendChild(errorEl);
        }
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }

    /**
     * Masquage d'erreur sur un champ
     */
    hideFieldError(field) {
        const errorEl = field.parentNode.querySelector('.field-error');
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    /**
     * Debouncing pour optimiser les performances
     */
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    /**
     * Throttling pour limiter la fréquence d'exécution
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Formatage des dates
     */
    formatDate(date, format = 'fr') {
        const d = new Date(date);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        return d.toLocaleDateString(format, options);
    }

    /**
     * Formatage des tailles de fichier
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Génération d'ID unique
     */
    generateId(prefix = 'admin') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Échappement HTML
     */
    escapeHtml(text) {
        if (typeof text !== 'string') return '';
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Copie dans le presse-papiers
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return true;
            } catch (fallbackErr) {
                document.body.removeChild(textArea);
                return false;
            }
        }
    }

    /**
     * Gestion des raccourcis clavier
     */
    setupKeyboardShortcuts(shortcuts) {
        document.addEventListener('keydown', (e) => {
            for (const [combination, action] of Object.entries(shortcuts)) {
                if (this.matchesShortcut(e, combination)) {
                    e.preventDefault();
                    action();
                    break;
                }
            }
        });
    }

    /**
     * Vérification de correspondance de raccourci
     */
    matchesShortcut(event, combination) {
        const parts = combination.toLowerCase().split('+');
        const key = parts.pop();
        
        const modifiers = {
            ctrl: event.ctrlKey,
            alt: event.altKey,
            shift: event.shiftKey,
            meta: event.metaKey
        };

        // Vérifier que tous les modificateurs requis sont présents
        for (const part of parts) {
            if (!modifiers[part]) return false;
        }

        // Vérifier que les modificateurs non requis ne sont pas présents
        for (const [mod, pressed] of Object.entries(modifiers)) {
            if (pressed && !parts.includes(mod)) return false;
        }

        return event.key.toLowerCase() === key;
    }

    /**
     * Animation de scroll vers un élément
     */
    scrollToElement(element, offset = 0) {
        const targetElement = typeof element === 'string' 
            ? document.querySelector(element) 
            : element;
            
        if (!targetElement) return;

        const elementPosition = targetElement.offsetTop;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    /**
     * Gestion de l'état d'activité
     */
    setupActivityTracker() {
        let isActive = true;
        let lastActivity = Date.now();

        const updateActivity = () => {
            lastActivity = Date.now();
            if (!isActive) {
                isActive = true;
                this.onActivityResume?.();
            }
        };

        // Événements d'activité
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, updateActivity, true);
        });

        // Vérification périodique d'inactivité
        setInterval(() => {
            if (Date.now() - lastActivity > 300000 && isActive) { // 5 minutes
                isActive = false;
                this.onActivityPause?.();
            }
        }, 60000); // Vérifier chaque minute

        return {
            isActive: () => isActive,
            getLastActivity: () => lastActivity
        };
    }

    /**
     * Gestion des erreurs globales
     */
    setupErrorHandling() {
        window.addEventListener('error', (event) => {
            console.error('Erreur JavaScript:', event.error);
            this.showToast('Une erreur inattendue s\'est produite', 'error');
        });

        window.addEventListener('unhandledrejection', (event) => {
            console.error('Promise rejetée:', event.reason);
            this.showToast('Erreur de traitement', 'error');
        });
    }

    /**
     * Sauvegarde d'urgence
     */
    setupEmergencyBackup(dataGetter, interval = 30000) {
        setInterval(() => {
            try {
                const data = dataGetter();
                const backup = {
                    timestamp: new Date().toISOString(),
                    data: data
                };
                localStorage.setItem('admin-emergency-backup', JSON.stringify(backup));
            } catch (error) {
                console.warn('Erreur lors de la sauvegarde d\'urgence:', error);
            }
        }, interval);
    }

    /**
     * Récupération de sauvegarde d'urgence
     */
    getEmergencyBackup() {
        try {
            const backup = localStorage.getItem('admin-emergency-backup');
            return backup ? JSON.parse(backup) : null;
        } catch (error) {
            console.warn('Erreur lors de la récupération de sauvegarde:', error);
            return null;
        }
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        // Nettoyer les modales créées
        const modal = document.getElementById('admin-confirm-modal');
        if (modal) modal.remove();

        const loader = document.getElementById('admin-loader');
        if (loader) loader.remove();

        // Nettoyer les toasts
        document.querySelectorAll('.admin-toast').forEach(toast => toast.remove());
    }
}

// Export pour utilisation dans d'autres modules
window.AdminUtilities = AdminUtilities;