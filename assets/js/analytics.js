/**
 * Module Analytics - Remmailleuse
 * Gestion du tracking Matomo avec respect de la confidentialité
 */

class AnalyticsManager {
    constructor() {
        this.settings = null;
        this.matomoTracker = null;
        this.consentGiven = false;
        this.init();
    }

    /**
     * Initialise le gestionnaire d'analytics
     */
    async init() {
        try {
            await this.loadSettings();
            if (this.settings?.analytics?.matomo?.enabled) {
                this.setupMatomo();
            }
        } catch (error) {
            console.error('Erreur initialisation analytics:', error);
        }
    }

    /**
     * Charge les paramètres depuis settings.json
     */
    async loadSettings() {
        try {
            const response = await fetch('/data/settings.json');
            this.settings = await response.json();
        } catch (error) {
            console.error('Erreur chargement settings:', error);
        }
    }

    /**
     * Configure Matomo
     */
    setupMatomo() {
        const config = this.settings.analytics.matomo;
        
        // Vérifier le consentement cookies si requis
        if (config.cookie_consent && !this.hasAnalyticsConsent()) {
            this.setupConsentListener();
            return;
        }

        this.initializeMatomo();
    }

    /**
     * Initialise le tracking Matomo
     */
    initializeMatomo() {
        const config = this.settings.analytics.matomo;
        
        // Créer le script de tracking Matomo
        window._paq = window._paq || [];
        const paq = window._paq;

        // Configuration basique
        paq.push(['trackPageView']);
        paq.push(['enableLinkTracking']);
        
        // Configuration avancée selon les paramètres
        if (config.anonymize_ip) {
            paq.push(['setDoNotTrack', true]);
        }
        
        if (config.respect_dnt) {
            paq.push(['setDoNotTrack', true]);
        }
        
        if (config.disable_cookies) {
            paq.push(['disableCookies']);
        }
        
        if (config.track_downloads) {
            paq.push(['enableFileTracking']);
        }
        
        if (config.track_outlinks) {
            paq.push(['enableLinkTracking']);
        }

        // Configuration du tracker
        paq.push(['setTrackerUrl', config.url + 'matomo.php']);
        paq.push(['setSiteId', config.site_id]);

        // Injection du script
        this.injectMatomoScript(config.url);
        
        this.consentGiven = true;
        this.matomoTracker = paq;
    }

    /**
     * Injecte le script Matomo dans la page
     */
    injectMatomoScript(matomoUrl) {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = matomoUrl + 'matomo.js';
        
        const firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);
    }

    /**
     * Vérifie si le consentement analytics a été donné
     */
    hasAnalyticsConsent() {
        const consent = localStorage.getItem('cookie_consent');
        if (!consent) return false;
        
        try {
            const consentData = JSON.parse(consent);
            return consentData.analytics === true;
        } catch (error) {
            return false;
        }
    }

    /**
     * Configure l'écoute du consentement
     */
    setupConsentListener() {
        // Écouter les changements de consentement
        window.addEventListener('storage', (e) => {
            if (e.key === 'cookie_consent' && !this.consentGiven) {
                if (this.hasAnalyticsConsent()) {
                    this.initializeMatomo();
                }
            }
        });

        // Écouter les événements personnalisés de consentement
        window.addEventListener('cookieConsentChanged', (e) => {
            if (e.detail.analytics && !this.consentGiven) {
                this.initializeMatomo();
            } else if (!e.detail.analytics && this.consentGiven) {
                this.disableTracking();
            }
        });
    }

    /**
     * Désactive le tracking
     */
    disableTracking() {
        if (this.matomoTracker) {
            this.matomoTracker.push(['optUserOut']);
        }
        this.consentGiven = false;
    }

    /**
     * Active le tracking
     */
    enableTracking() {
        if (!this.consentGiven && this.settings?.analytics?.matomo?.enabled) {
            this.initializeMatomo();
        } else if (this.matomoTracker) {
            this.matomoTracker.push(['forgetUserOptOut']);
        }
    }

    /**
     * Track un événement personnalisé
     */
    trackEvent(category, action, name = null, value = null) {
        if (!this.consentGiven || !this.matomoTracker) return;
        
        const params = ['trackEvent', category, action];
        if (name) params.push(name);
        if (value) params.push(value);
        
        this.matomoTracker.push(params);
    }

    /**
     * Track une page vue
     */
    trackPageView(customTitle = null) {
        if (!this.consentGiven || !this.matomoTracker) return;
        
        if (customTitle) {
            this.matomoTracker.push(['setDocumentTitle', customTitle]);
        }
        
        this.matomoTracker.push(['trackPageView']);
    }

    /**
     * Track un téléchargement
     */
    trackDownload(url, filename = null) {
        if (!this.consentGiven || !this.matomoTracker) return;
        
        this.matomoTracker.push(['trackLink', url, 'download', filename]);
    }

    /**
     * Track un formulaire
     */
    trackFormSubmit(formName, success = true) {
        const action = success ? 'submit_success' : 'submit_error';
        this.trackEvent('Form', action, formName);
    }

    /**
     * Track la galerie
     */
    trackGalleryView(category, image = null) {
        this.trackEvent('Gallery', 'view', category, image);
    }

    /**
     * Track les clics sur les liens de contact
     */
    trackContactClick(type) {
        this.trackEvent('Contact', 'click', type);
    }

    /**
     * Obtenir des informations sur le tracking
     */
    getTrackingInfo() {
        return {
            enabled: this.consentGiven,
            hasConsent: this.hasAnalyticsConsent(),
            settings: this.settings?.analytics?.matomo || null
        };
    }
}

// Instance globale
window.analyticsManager = new AnalyticsManager();

// Fonctions utilitaires globales
window.trackEvent = function(category, action, name = null, value = null) {
    return window.analyticsManager.trackEvent(category, action, name, value);
};

window.trackPageView = function(customTitle = null) {
    return window.analyticsManager.trackPageView(customTitle);
};

window.trackDownload = function(url, filename = null) {
    return window.analyticsManager.trackDownload(url, filename);
};

// Auto-tracking pour les éléments courants
document.addEventListener('DOMContentLoaded', () => {
    // Track les clics sur les liens externes
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (link && link.hostname !== window.location.hostname) {
            window.analyticsManager.trackEvent('Outlink', 'click', link.href);
        }
    });

    // Track les soumissions de formulaires
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.tagName === 'FORM' && form.id) {
            window.analyticsManager.trackFormSubmit(form.id);
        }
    });
});

// Export pour les modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnalyticsManager;
}