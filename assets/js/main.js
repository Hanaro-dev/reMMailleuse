/**
 * Site Remmailleuse - Application Principal Refactorisée
 * Version optimisée avec séparation des responsabilités
 */

class RemmailleuseApp {
    constructor() {
        this.dataManager = new DataManager();
        this.uiComponents = null;
        this.isLoading = false;
        this.init();
    }

    async init() {
        try {
            this.showLoading();
            await this.loadData();
            this.setupComponents();
            this.setupEventListeners();
            this.setupNavigation();
            this.setupScrollReveal();
            this.renderContent();
            this.hideLoading();
            this.showToast('🎉 Site chargé avec succès !', 'success');
        } catch (error) {
            console.error('Erreur d\'initialisation:', error);
            this.hideLoading();
            this.showToast('❌ Erreur de chargement: ' + error.message, 'error');
            // Charger les données par défaut
            this.loadDefaultData();
        }
    }

    /**
     * Chargement des données
     */
    async loadData() {
        try {
            await this.dataManager.loadAllData();
        } catch (error) {
            console.warn('Utilisation des données par défaut:', error);
            this.dataManager.data = this.dataManager.getDefaultData();
        }
    }

    /**
     * Chargement des données par défaut
     */
    loadDefaultData() {
        this.dataManager.data = this.dataManager.getDefaultData();
        this.setupComponents();
        this.renderContent();
    }

    /**
     * Configuration des composants
     */
    setupComponents() {
        this.uiComponents = new UIComponents(this.dataManager);
    }

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Calculateur de prix
        this.setupPriceCalculator();
        
        // Gestion des fichiers
        this.setupFileUpload();
        
        // Modal de galerie
        this.setupGalleryModal();
        
        // Recherche
        this.setupSearch();
    }

    /**
     * Navigation
     */
    setupNavigation() {
        // Menu mobile
        this.setupMobileMenu();
        
        // Navigation smooth scroll
        this.setupSmoothScroll();
        
        // Navigation active
        this.setupActiveNavigation();
    }

    /**
     * Menu mobile
     */
    setupMobileMenu() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

        const toggleMobileMenu = () => {
            mobileMenu.classList.toggle('active');
            mobileMenuOverlay.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        };

        const closeMobileMenu = () => {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.classList.remove('menu-open');
        };

        mobileMenuBtn?.addEventListener('click', toggleMobileMenu);
        mobileMenuClose?.addEventListener('click', closeMobileMenu);
        mobileMenuOverlay?.addEventListener('click', closeMobileMenu);

        // Fermer le menu lors du clic sur un lien
        document.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }

    /**
     * Smooth scroll
     */
    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    const offset = 80; // Hauteur de la navbar
                    Utilities.scrollToElement(anchor.getAttribute('href'), offset);
                }
            });
        });
    }

    /**
     * Navigation active
     */
    setupActiveNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        const updateActiveNav = Utilities.throttle(() => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (window.scrollY >= sectionTop - 200) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        }, 100);

        window.addEventListener('scroll', updateActiveNav);
    }

    /**
     * Calculateur de prix
     */
    setupPriceCalculator() {
        const form = document.getElementById('price-calculator-form');
        const resultDiv = document.getElementById('estimate-result');

        form?.addEventListener('submit', (e) => {
            e.preventDefault();

            const garmentType = document.getElementById('garment-type').value;
            const damageType = document.getElementById('damage-type').value;
            const damageSize = document.getElementById('damage-size').value;

            if (!garmentType || !damageType) {
                this.showToast('⚠️ Veuillez remplir tous les champs obligatoires', 'warning');
                return;
            }

            const estimate = Utilities.calculateEstimatedPrice(garmentType, damageType, damageSize);
            
            resultDiv.querySelector('.estimate-price').textContent = `Estimation: ${estimate.display}`;
            resultDiv.style.display = 'block';

            this.showToast('💰 Estimation calculée !', 'success');
        });
    }

    /**
     * Upload de fichiers
     */
    setupFileUpload() {
        const fileUpload = document.getElementById('file-upload');
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');

        // Drag & Drop
        fileUpload?.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload?.addEventListener('dragleave', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
        });

        fileUpload?.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            this.handleFileSelection(files);
        });

        // Click pour sélectionner
        fileUpload?.addEventListener('click', () => {
            fileInput.click();
        });

        // Sélection de fichiers
        fileInput?.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.handleFileSelection(files);
        });
    }

    /**
     * Gestion de la sélection de fichiers
     */
    handleFileSelection(files) {
        const filePreview = document.getElementById('file-preview');
        const maxSize = 5 * 1024 * 1024; // 5MB
        const validFiles = [];

        files.forEach(file => {
            if (!Utilities.isValidImageFile(file)) {
                this.showToast(`❌ ${file.name} n'est pas une image valide`, 'error');
                return;
            }

            if (file.size > maxSize) {
                this.showToast(`❌ ${file.name} est trop volumineux (max 5MB)`, 'error');
                return;
            }

            validFiles.push(file);
        });

        if (validFiles.length === 0) return;

        filePreview.textContent = '';

        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'file-preview-item';
                const previewHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button class="file-remove" onclick="this.parentElement.remove()">×</button>
                `;
                window.safeHTML(previewItem, previewHTML);
                filePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });

        this.showToast(`✅ ${validFiles.length} fichier(s) ajouté(s)`, 'success');
    }

    /**
     * Modal de galerie
     */
    setupGalleryModal() {
        const modal = document.getElementById('gallery-modal');
        const modalClose = document.getElementById('modal-close');
        const modalOverlay = document.getElementById('modal-overlay');
        const toggleBefore = document.getElementById('toggle-before');
        const toggleAfter = document.getElementById('toggle-after');
        const imageBefore = document.getElementById('modal-image-before');
        const imageAfter = document.getElementById('modal-image-after');

        const closeModal = () => {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        };

        modalClose?.addEventListener('click', closeModal);
        modalOverlay?.addEventListener('click', closeModal);

        // Basculer entre avant/après
        toggleBefore?.addEventListener('click', () => {
            imageBefore.style.display = 'block';
            imageAfter.style.display = 'none';
            toggleBefore.classList.add('active');
            toggleAfter.classList.remove('active');
        });

        toggleAfter?.addEventListener('click', () => {
            imageBefore.style.display = 'none';
            imageAfter.style.display = 'block';
            toggleBefore.classList.remove('active');
            toggleAfter.classList.add('active');
        });

        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });
    }

    /**
     * Recherche
     */
    setupSearch() {
        // Placeholder pour future fonctionnalité de recherche
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            const debouncedSearch = Utilities.debounce((query) => {
                this.performSearch(query);
            }, 300);

            searchInput.addEventListener('input', (e) => {
                debouncedSearch(e.target.value);
            });
        }
    }

    /**
     * Recherche dans le contenu
     */
    performSearch(query) {
        // Implémentation future
        console.log('Recherche:', query);
    }

    /**
     * Scroll reveal
     */
    setupScrollReveal() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Rendu du contenu
     */
    renderContent() {
        if (this.uiComponents) {
            this.uiComponents.renderAll();
        }
    }

    /**
     * Utilitaires d'affichage
     */
    showLoading() {
        if (this.uiComponents) {
            this.uiComponents.showLoading();
        }
    }

    hideLoading() {
        if (this.uiComponents) {
            this.uiComponents.hideLoading();
        }
    }

    showToast(message, type = 'info') {
        if (this.uiComponents) {
            this.uiComponents.showToast(message, type);
        }
    }
}

// Initialisation de l'application
document.addEventListener('DOMContentLoaded', () => {
    window.app = new RemmailleuseApp();
});

// Export global
window.RemmailleuseApp = RemmailleuseApp;