/**
 * Gestion des tokens CSRF côté client
 * Automatise l'ajout des tokens CSRF aux formulaires et requêtes AJAX
 */

class CSRFManager {
    constructor() {
        this.token = null;
        this.tokenName = 'csrf_token';
        this.init();
    }

    /**
     * Initialise le gestionnaire CSRF
     */
    async init() {
        await this.refreshToken();
        this.setupFormHandlers();
        this.setupAjaxDefaults();
    }

    /**
     * Récupère un nouveau token CSRF du serveur
     */
    async refreshToken() {
        try {
            const response = await fetch('../api/csrf.php?action=get_token', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.token = data.token;
                    this.tokenName = data.name;
                    this.updateHiddenFields();
                    return true;
                }
            }
        } catch (error) {
            // Erreur lors du rafraîchissement du token
        }
        return false;
    }

    /**
     * Met à jour tous les champs hidden CSRF dans le DOM
     */
    updateHiddenFields() {
        const existingFields = document.querySelectorAll('input[name="csrf_token"]');
        existingFields.forEach(field => {
            field.value = this.token;
        });
    }

    /**
     * Ajoute un champ CSRF hidden à un formulaire
     */
    addTokenToForm(form) {
        if (!this.token) return;

        // Supprimer l'ancien champ s'il existe
        const existingField = form.querySelector('input[name="csrf_token"]');
        if (existingField) {
            existingField.remove();
        }

        // Ajouter le nouveau champ
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'csrf_token';
        hiddenField.value = this.token;
        form.appendChild(hiddenField);
    }

    /**
     * Configure les gestionnaires d'événements pour les formulaires
     */
    setupFormHandlers() {
        // Ajouter les tokens aux formulaires existants
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            this.addTokenToForm(form);
        });

        // Observer les nouveaux formulaires ajoutés dynamiquement
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'FORM') {
                            this.addTokenToForm(node);
                        }
                        // Chercher les formulaires dans les nœuds ajoutés
                        const forms = node.querySelectorAll && node.querySelectorAll('form');
                        if (forms) {
                            forms.forEach(form => this.addTokenToForm(form));
                        }
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
     * Configure les paramètres par défaut pour les requêtes AJAX
     */
    setupAjaxDefaults() {
        // Intercepter les requêtes fetch
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            // Ajouter le token CSRF aux requêtes POST
            if (options.method === 'POST' && this.token) {
                if (options.body instanceof FormData) {
                    options.body.append('csrf_token', this.token);
                } else if (options.headers && options.headers['Content-Type'] === 'application/json') {
                    // Pour les requêtes JSON
                    options.headers['X-CSRF-Token'] = this.token;
                }
            }
            return originalFetch(url, options);
        };
    }

    /**
     * Ajoute le token CSRF à un objet de données
     */
    addTokenToData(data) {
        if (this.token) {
            data.csrf_token = this.token;
        }
        return data;
    }

    /**
     * Ajoute le token CSRF aux headers d'une requête
     */
    addTokenToHeaders(headers = {}) {
        if (this.token) {
            headers['X-CSRF-Token'] = this.token;
        }
        return headers;
    }

    /**
     * Rafraîchit le token et met à jour les formulaires
     */
    async rotate() {
        const success = await this.refreshToken();
        if (success) {
            this.updateHiddenFields();
        }
        return success;
    }

    /**
     * Vérifie si un token est disponible
     */
    isReady() {
        return this.token !== null;
    }

    /**
     * Obtient le token actuel
     */
    getToken() {
        return this.token;
    }

    /**
     * Obtient le nom du champ token
     */
    getTokenName() {
        return this.tokenName;
    }
}

// Instance globale du gestionnaire CSRF
window.csrfManager = new CSRFManager();

// Fonction utilitaire pour faciliter l'utilisation
window.addCSRFToken = function(data) {
    return window.csrfManager.addTokenToData(data);
};

// Fonction utilitaire pour les headers
window.getCSRFHeaders = function() {
    return window.csrfManager.addTokenToHeaders();
};

// Attendre que le DOM soit prêt
document.addEventListener('DOMContentLoaded', () => {
    // Le gestionnaire CSRF est déjà initialisé
    // Rafraîchir le token toutes les 30 minutes
    setInterval(() => {
        window.csrfManager.rotate();
    }, 30 * 60 * 1000);
});

// Rafraîchir le token avant que la page ne soit fermée
window.addEventListener('beforeunload', () => {
    // Pas de rafraîchissement nécessaire lors de la fermeture
});

// Exporter pour les modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CSRFManager;
}