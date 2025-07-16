/**
 * Gestionnaire d'authentification pour l'interface admin
 * Gère la connexion, la vérification de session et le rafraîchissement automatique
 */

class AuthManager {
    constructor() {
        this.isAuthenticated = false;
        this.refreshInterval = null;
        this.sessionTimeout = 30 * 60 * 1000; // 30 minutes
    }

    /**
     * Vérification de l'authentification
     */
    async checkAuthentication() {
        try {
            const response = await fetch('../api/auth.php?action=check');
            const data = await response.json();
            this.isAuthenticated = data.authenticated === true;
            return this.isAuthenticated;
        } catch (error) {
            console.error('Erreur de vérification d\'authentification:', error);
            this.isAuthenticated = false;
            return false;
        }
    }

    /**
     * Connexion utilisateur
     */
    async login(username, password) {
        try {
            const response = await fetch('../api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    username: username,
                    password: password
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.isAuthenticated = true;
                this.setupSessionRefresh();
                return { success: true, message: data.message };
            } else {
                return { success: false, message: data.message || 'Échec de la connexion' };
            }
        } catch (error) {
            console.error('Erreur de connexion:', error);
            return { success: false, message: 'Erreur de connexion au serveur' };
        }
    }

    /**
     * Déconnexion
     */
    async logout() {
        try {
            await fetch('../api/auth.php?action=logout', {
                method: 'POST'
            });
        } catch (error) {
            console.error('Erreur de déconnexion:', error);
        } finally {
            this.isAuthenticated = false;
            this.clearSessionRefresh();
            window.location.href = './login.html';
        }
    }

    /**
     * Configuration du rafraîchissement automatique de session
     */
    setupSessionRefresh() {
        this.clearSessionRefresh();
        
        this.refreshInterval = setInterval(async () => {
            try {
                const response = await fetch('../api/auth.php?action=refresh', {
                    method: 'POST'
                });
                
                if (!response.ok) {
                    this.handleSessionExpired();
                }
            } catch (error) {
                console.error('Erreur de rafraîchissement de session:', error);
                this.handleSessionExpired();
            }
        }, this.sessionTimeout);
    }

    /**
     * Nettoyage du rafraîchissement automatique
     */
    clearSessionRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Gestion de l'expiration de session
     */
    handleSessionExpired() {
        this.isAuthenticated = false;
        this.clearSessionRefresh();
        alert('Votre session a expiré. Vous allez être redirigé vers la page de connexion.');
        window.location.href = './login.html';
    }

    /**
     * Vérification de l'état d'authentification
     */
    isUserAuthenticated() {
        return this.isAuthenticated;
    }

    /**
     * Redirection vers la page de connexion si non authentifié
     */
    requireAuth() {
        if (!this.isAuthenticated) {
            window.location.href = './login.html';
            return false;
        }
        return true;
    }

    /**
     * Gestion des erreurs d'authentification
     */
    handleAuthError(error) {
        console.error('Erreur d\'authentification:', error);
        
        if (error.status === 401) {
            this.handleSessionExpired();
        } else if (error.status === 403) {
            alert('Accès non autorisé.');
            window.location.href = './login.html';
        } else {
            alert('Erreur d\'authentification. Veuillez réessayer.');
        }
    }

    /**
     * Middleware pour les requêtes authentifiées
     */
    async authenticatedFetch(url, options = {}) {
        if (!this.isAuthenticated) {
            this.requireAuth();
            return null;
        }

        try {
            const response = await fetch(url, {
                ...options,
                credentials: 'include' // Inclure les cookies de session
            });

            if (response.status === 401) {
                this.handleSessionExpired();
                return null;
            }

            return response;
        } catch (error) {
            console.error('Erreur lors de la requête authentifiée:', error);
            throw error;
        }
    }

    /**
     * Initialisation du gestionnaire d'authentification
     */
    async init() {
        const isAuth = await this.checkAuthentication();
        
        if (isAuth) {
            this.setupSessionRefresh();
        }
        
        return isAuth;
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        this.clearSessionRefresh();
        this.isAuthenticated = false;
    }
}

// Export pour utilisation dans d'autres modules
window.AuthManager = AuthManager;