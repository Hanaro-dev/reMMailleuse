/**
 * Admin Remmailleuse - Interface d'administration refactorisée
 * Architecture modulaire avec séparation des responsabilités
 */

class AdminApp {
    constructor() {
        this.currentSection = 'hero-section';
        this.isInitialized = false;
        
        // Modules
        this.authManager = null;
        this.dataManager = null;
        this.formManager = null;
        this.renderManager = null;
        this.imageManager = null;
        this.utilities = null;
        this.uiManager = null;
        
        this.init();
    }

    /**
     * Initialisation de l'application admin
     */
    async init() {
        try {
            this.showLoading('Initialisation de l\'administration...');
            
            // Initialiser les modules de base
            this.initializeModules();
            
            // Vérifier l'authentification d'abord
            const authCheck = await this.authManager.checkAuthentication();
            if (!authCheck) {
                window.location.href = './login.html';
                return;
            }
            
            // Charger les données
            await this.dataManager.loadData();
            
            // Configurer l'interface
            this.setupInterface();
            
            // Démarrer les fonctionnalités
            this.startServices();
            
            this.isInitialized = true;
            this.hideLoading();
            this.showStatus('🎉 Interface d\'administration chargée !', 'success');
            
        } catch (error) {
            console.error('Erreur d\'initialisation admin:', error);
            this.hideLoading();
            this.showStatus('❌ Erreur de chargement de l\'administration', 'error');
        }
    }

    /**
     * Initialisation des modules
     */
    initializeModules() {
        // Modules d'infrastructure
        this.utilities = new AdminUtilities();
        this.authManager = new AuthManager();
        
        // Modules métier
        this.dataManager = new AdminDataManager(this.authManager);
        this.formManager = new AdminFormManager(this.dataManager);
        this.renderManager = new AdminRenderManager(this.dataManager);
        this.imageManager = new AdminImageManager(this.dataManager);
        
        // Module UI (utilise le ContentManager existant si disponible)
        this.uiManager = window.UIManager ? new UIManager(this.dataManager) : null;
        
        // Exposer les managers globalement pour les callbacks HTML
        window.adminApp = this;
    }

    /**
     * Configuration de l'interface
     */
    setupInterface() {
        // Populer tous les champs
        this.formManager.populateAllFields();
        
        // Rendre les éléments dynamiques
        this.renderManager.renderAll();
        
        // Configurer la navigation
        this.setupNavigation();
        
        // Configurer les événements globaux
        this.setupEventListeners();
    }

    /**
     * Démarrage des services
     */
    startServices() {
        // Sauvegarde automatique
        this.dataManager.setupAutosave();
        
        // Rafraîchissement de session
        this.authManager.setupSessionRefresh();
        
        // Gestion d'erreurs
        this.utilities.setupErrorHandling();
        
        // Sauvegarde d'urgence
        this.utilities.setupEmergencyBackup(() => this.dataManager.getData());
        
        // Raccourcis clavier
        this.setupKeyboardShortcuts();
    }

    /**
     * Configuration de la navigation
     */
    setupNavigation() {
        // Gestion des liens de navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = link.getAttribute('href').substring(1);
                this.showSection(sectionId, link);
            });
        });

        // Section par défaut
        this.showSection(this.currentSection);
    }

    /**
     * Configuration des événements globaux
     */
    setupEventListeners() {
        // Validation en temps réel des couleurs
        document.addEventListener('input', (e) => {
            if (e.target.type === 'color') {
                this.formManager.previewColorChange(e.target);
            }
        });

        // Gestion de la fermeture
        window.addEventListener('beforeunload', (e) => {
            if (this.dataManager.hasUnsavedData()) {
                e.preventDefault();
                e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
                return e.returnValue;
            }
        });
    }

    /**
     * Configuration des raccourcis clavier
     */
    setupKeyboardShortcuts() {
        const shortcuts = {
            'ctrl+s': () => this.saveAll(),
            'ctrl+e': () => this.exportData(),
            'ctrl+p': () => this.previewSite(),
            'ctrl+1': () => this.showSection('hero-section'),
            'ctrl+2': () => this.showSection('expertise-section'),
            'ctrl+3': () => this.showSection('services-section'),
            'ctrl+4': () => this.showSection('gallery-section'),
            'ctrl+5': () => this.showSection('contact-section'),
            'ctrl+6': () => this.showSection('settings-section')
        };

        this.utilities.setupKeyboardShortcuts(shortcuts);
    }

    /**
     * Affichage d'une section
     */
    showSection(sectionId, linkElement = null) {
        // Masquer toutes les sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Désactiver tous les liens
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Afficher la section demandée
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.add('active');
            this.currentSection = sectionId;
        }
        
        // Activer le lien approprié
        if (linkElement) {
            linkElement.classList.add('active');
        } else {
            // Trouver et activer le lien correspondant
            const targetLink = document.querySelector(`[href="#${sectionId}"]`);
            if (targetLink) {
                targetLink.classList.add('active');
            }
        }

        // Populer les champs de la section si nécessaire
        if (this.formManager) {
            this.formManager.populateAllFields();
        }
    }

    /**
     * Sauvegarde automatique
     */
    async autoSave(section = null) {
        if (!this.isInitialized) return;

        try {
            // Collecter les données des formulaires
            this.formManager.collectFormData();
            
            // Sauvegarder
            const result = await this.dataManager.saveData(section);
            
            if (result.success) {
                const sectionName = section ? `section "${section}"` : 'modifications';
                this.showStatus(`✅ ${sectionName.charAt(0).toUpperCase() + sectionName.slice(1)} sauvegardée(s) !`, 'success');
            }
            
        } catch (error) {
            console.error('Erreur de sauvegarde automatique:', error);
            this.showStatus('❌ Erreur lors de la sauvegarde', 'error');
        }
    }

    /**
     * Sauvegarde complète
     */
    async saveAll() {
        if (!this.isInitialized) return;

        this.showLoading('Sauvegarde en cours...');
        
        try {
            // Collecter toutes les données
            this.formManager.collectFormData();
            
            // Sauvegarder tout
            const result = await this.dataManager.saveData();
            
            this.hideLoading();
            
            if (result.success) {
                this.showStatus('💾 Toutes les modifications ont été sauvegardées !', 'success');
            }
            
        } catch (error) {
            this.hideLoading();
            this.showStatus('❌ Erreur lors de la sauvegarde complète', 'error');
            console.error('Erreur de sauvegarde:', error);
        }
    }

    /**
     * Prévisualisation du site
     */
    previewSite() {
        // Sauvegarder avant la prévisualisation
        this.autoSave().then(() => {
            window.open('../index.html', '_blank');
        });
    }

    /**
     * Export des données
     */
    exportData() {
        try {
            this.formManager.collectFormData();
            this.dataManager.exportData();
            this.showStatus('📤 Données exportées !', 'success');
        } catch (error) {
            this.showStatus('❌ Erreur lors de l\'export', 'error');
            console.error('Erreur d\'export:', error);
        }
    }

    /**
     * Import des données
     */
    async importData(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        try {
            this.showLoading('Import en cours...');
            
            await this.dataManager.importData(file);
            
            // Re-rendre l'interface
            this.formManager.populateAllFields();
            this.renderManager.renderAll();
            
            this.hideLoading();
            this.showStatus('📥 Données importées avec succès !', 'success');
            
        } catch (error) {
            this.hideLoading();
            this.showStatus(`❌ Erreur lors de l'import : ${error.message}`, 'error');
            console.error('Erreur d\'import:', error);
        }
    }

    /**
     * Application de l'aperçu du thème
     */
    applyThemePreview() {
        if (this.formManager.applyThemePreview()) {
            this.showStatus('🎨 Aperçu des couleurs appliqué !', 'info');
        }
    }

    /**
     * Actions sur les étapes de processus
     */
    addProcessStep() {
        const newStep = this.renderManager.addProcessStep();
        if (newStep) {
            this.showStatus('➕ Nouvelle étape ajoutée !', 'success');
        }
    }

    removeProcessStepWithConfirm(index) {
        this.utilities.showConfirmModal(
            'Supprimer l\'étape',
            'Êtes-vous sûr de vouloir supprimer cette étape ?',
            () => {
                if (this.renderManager.removeProcessStep(index)) {
                    this.showStatus('🗑️ Étape supprimée', 'warning');
                }
            }
        );
    }

    /**
     * Actions sur les éléments de galerie
     */
    addGalleryItem() {
        const newItem = this.renderManager.addGalleryItem();
        if (newItem) {
            this.showStatus('➕ Nouvelle réalisation ajoutée !', 'success');
        }
    }

    removeGalleryItemWithConfirm(index) {
        this.utilities.showConfirmModal(
            'Supprimer la réalisation',
            'Êtes-vous sûr de vouloir supprimer cette réalisation ?',
            () => {
                if (this.renderManager.removeGalleryItem(index)) {
                    this.showStatus('🗑️ Réalisation supprimée', 'warning');
                }
            }
        );
    }

    /**
     * Actions sur les services
     */
    addService() {
        const newService = this.renderManager.addService();
        if (newService) {
            this.showStatus('➕ Nouveau service ajouté !', 'success');
        }
    }

    removeServiceWithConfirm(index) {
        this.utilities.showConfirmModal(
            'Supprimer le service',
            'Êtes-vous sûr de vouloir supprimer ce service ?',
            () => {
                if (this.renderManager.removeService(index)) {
                    this.showStatus('🗑️ Service supprimé', 'warning');
                }
            }
        );
    }

    /**
     * Utilitaires d'affichage
     */
    showLoading(message = 'Chargement...') {
        if (this.utilities) {
            this.utilities.showLoading(message);
        } else {
            // Fallback
            const modal = document.getElementById('loading-modal');
            if (modal) {
                modal.style.display = 'flex';
                const messageEl = modal.querySelector('p');
                if (messageEl) messageEl.textContent = message;
            }
        }
    }

    hideLoading() {
        if (this.utilities) {
            this.utilities.hideLoading();
        } else {
            // Fallback
            const modal = document.getElementById('loading-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    }

    showStatus(message, type = 'success') {
        if (this.utilities) {
            this.utilities.showToast(message, type);
        } else {
            // Fallback vers l'ancien système
            const statusBar = document.getElementById('status-bar');
            if (statusBar) {
                statusBar.textContent = message;
                statusBar.className = `status-bar show ${type}`;
                setTimeout(() => {
                    statusBar.classList.remove('show');
                }, 4000);
            }
        }
    }

    /**
     * Gestion des modales de confirmation
     */
    confirmAction(title, message, callback) {
        if (this.utilities) {
            this.utilities.showConfirmModal(title, message, callback);
        } else {
            // Fallback
            if (confirm(message)) {
                callback();
            }
        }
    }

    closeConfirmModal() {
        if (this.utilities) {
            this.utilities.hideConfirmModal();
        }
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        // Nettoyer les modules
        if (this.dataManager) this.dataManager.destroy();
        if (this.formManager) this.formManager.destroy();
        if (this.renderManager) this.renderManager.destroy();
        if (this.imageManager) this.imageManager.destroy();
        if (this.utilities) this.utilities.destroy();
        if (this.authManager) this.authManager.destroy();
        if (this.uiManager) this.uiManager.destroy();
        
        this.isInitialized = false;
    }
}

// ===== FONCTIONS GLOBALES POUR COMPATIBILITÉ HTML =====

function showSection(sectionId, linkElement) {
    if (window.adminApp) {
        window.adminApp.showSection(sectionId, linkElement);
    }
}

function autoSave(section) {
    if (window.adminApp) {
        window.adminApp.autoSave(section);
    }
}

function saveAll() {
    if (window.adminApp) {
        window.adminApp.saveAll();
    }
}

function previewSite() {
    if (window.adminApp) {
        window.adminApp.previewSite();
    }
}

function exportData() {
    if (window.adminApp) {
        window.adminApp.exportData();
    }
}

function addGalleryItem() {
    if (window.adminApp) {
        window.adminApp.addGalleryItem();
    }
}

function addService() {
    if (window.adminApp) {
        window.adminApp.addService();
    }
}

function addProcessStep() {
    if (window.adminApp) {
        window.adminApp.addProcessStep();
    }
}

function applyThemePreview() {
    if (window.adminApp) {
        window.adminApp.applyThemePreview();
    }
}

function closeConfirmModal() {
    if (window.adminApp) {
        window.adminApp.closeConfirmModal();
    }
}

// Fonctions spécifiques pour les suppressions avec confirmation
function removeProcessStep(index) {
    if (window.adminApp) {
        window.adminApp.removeProcessStepWithConfirm(index);
    }
}

function removeGalleryItem(index) {
    if (window.adminApp) {
        window.adminApp.removeGalleryItemWithConfirm(index);
    }
}

function removeService(index) {
    if (window.adminApp) {
        window.adminApp.removeServiceWithConfirm(index);
    }
}

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
    window.adminApp = new AdminApp();
    
    // Gestion des fichiers d'import
    const importInput = document.getElementById('import-data');
    if (importInput) {
        importInput.addEventListener('change', (e) => {
            window.adminApp.importData(e.target);
        });
    }
    
    // Gestion du drag & drop pour l'import
    document.addEventListener('dragover', (e) => {
        e.preventDefault();
    });
    
    document.addEventListener('drop', (e) => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/json') {
            const fakeInput = { files: [files[0]] };
            window.adminApp.importData(fakeInput);
        }
    });
});

// ===== GESTION DES ERREURS =====
window.addEventListener('error', (e) => {
    console.error('Erreur JavaScript Admin:', e.error);
    if (window.adminApp) {
        window.adminApp.showStatus('❌ Une erreur est survenue', 'error');
    }
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Promise rejetée Admin:', e.reason);
    if (window.adminApp) {
        window.adminApp.showStatus('❌ Erreur de traitement', 'error');
    }
});

// Export pour tests et utilisation externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminApp;
}