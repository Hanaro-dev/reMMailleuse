/**
 * Site Remmailleuse - Scripts Principaux
 * Gestion du contenu dynamique, navigation et interactions
 */

class RemmailleuseApp {
    constructor() {
        this.data = {};
        this.isLoading = false;
        this.init();
    }

    async init() {
        try {
            this.showLoading();
            await this.loadData();
            this.setupEventListeners();
            this.setupNavigation();
            this.setupScrollReveal();
            this.renderContent();
            this.hideLoading();
            this.showToast('🎉 Site chargé avec succès !', 'success');
        } catch (error) {
            // Erreur d'initialisation
            console.error('Erreur d\'initialisation:', error);
            this.hideLoading();
            this.showToast('❌ Erreur de chargement: ' + error.message, 'error');
        }
    }

    // ===== CHARGEMENT DES DONNÉES =====
    async loadData() {
        try {
            const [contentResponse, servicesResponse, galleryResponse, settingsResponse] = await Promise.all([
                fetch('data/content.json'),
                fetch('data/services.json'),
                fetch('data/gallery.json'),
                fetch('data/settings.json')
            ]);

            if (!contentResponse.ok) throw new Error('Impossible de charger le contenu');
            if (!servicesResponse.ok) throw new Error('Impossible de charger les services');
            if (!galleryResponse.ok) throw new Error('Impossible de charger la galerie');
            if (!settingsResponse.ok) throw new Error('Impossible de charger les paramètres');

            this.data = {
                content: await contentResponse.json(),
                services: await servicesResponse.json(),
                gallery: await galleryResponse.json(),
                settings: await settingsResponse.json()
            };

            // Appliquer les paramètres
            this.applySettings();
        } catch (error) {
            // Erreur lors du chargement des données
            console.error('Erreur lors du chargement des données:', error);
            throw new Error('Impossible de charger les données du site: ' + error.message);
        }
    }

    getDefaultData() {
        return {
            content: {
                hero: {
                    title: "L'art de redonner vie à vos tissus précieux",
                    subtitle: "Remaillage traditionnel & réparation invisible depuis plus de 20 ans",
                    cta: { text: "Découvrir mon savoir-faire", link: "#expertise" }
                },
                expertise: {
                    title: "Mon Expertise",
                    intro: {
                        name: "Mme Monod, Artisane Remmailleuse",
                        description: [
                            "Passionnée par les techniques traditionnelles de remaillage, je redonne vie à vos tissus et lainages les plus précieux.",
                            "Que ce soit pour refermer un trou de mite avec la plus grande minutie ou effectuer du raccommodage avec ma fidèle machine Elna vintage, j'apporte le plus grand soin à rénover vos tissus à l'identique."
                        ]
                    },
                    process: [
                        { step: 1, icon: "🔍", title: "Diagnostic", description: "Analyse minutieuse de la pièce" },
                        { step: 2, icon: "🧵", title: "Remaillage", description: "Reconstruction maille par maille" },
                        { step: 3, icon: "✨", title: "Finition", description: "Réparation invisible" }
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
            },
            services: {
                services: [
                    { id: "remaillage", icon: "🧵", name: "Remaillage classique", description: "Reconstruction maille par maille pour lainages", price: "15-40€" },
                    { id: "mite", icon: "🔍", name: "Trous de mite", description: "Réparation invisible minutieuse", price: "20-35€" },
                    { id: "bas", icon: "🧦", name: "Bas de contention", description: "Raccommodage machine spécialisée", price: "15-25€" },
                    { id: "renovation", icon: "✨", name: "Rénovation tissus", description: "Restauration à l'identique", price: "Sur devis" }
                ]
            },
            gallery: {
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
            },
            settings: {
                theme: {
                    colors: {
                        primary: "#D4896B",
                        secondary: "#9CAF9A",
                        accent: "#8B6F47",
                        neutral: "#F5F1EB"
                    }
                }
            }
        };
    }

    applySettings() {
        if (this.data.settings?.theme?.colors) {
            const root = document.documentElement;
            const colors = this.data.settings.theme.colors;
            
            Object.entries(colors).forEach(([key, value]) => {
                root.style.setProperty(`--color-${key}`, value);
            });
        }

        // Mettre à jour le titre de la page
        if (this.data.settings?.seo?.title) {
            document.title = this.data.settings.seo.title;
        }

        // Mettre à jour la meta description
        if (this.data.settings?.seo?.description) {
            const metaDesc = document.querySelector('meta[name="description"]');
            if (metaDesc) metaDesc.content = this.data.settings.seo.description;
        }
    }

    // ===== RENDU DU CONTENU =====
    renderContent() {
        this.renderHero();
        this.renderExpertise();
        this.renderGallery();
        this.renderServices();
        this.renderContact();
        this.renderFooter();
    }

    renderHero() {
        const hero = this.data.content.hero;
        if (!hero) return;

        const titleElement = document.getElementById('hero-title');
        const subtitleElement = document.getElementById('hero-subtitle');
        const ctaElement = document.getElementById('hero-cta');
        const ctaTextElement = document.getElementById('hero-cta-text');

        if (titleElement) {
            titleElement.innerHTML = hero.title.includes('redonner vie') 
                ? hero.title.replace('redonner vie', '<span class="text-gradient">redonner vie</span>')
                : hero.title;
        }
        if (subtitleElement) subtitleElement.textContent = hero.subtitle;
        if (ctaTextElement) ctaTextElement.textContent = hero.cta.text;
        if (ctaElement && hero.cta.link) {
            ctaElement.addEventListener('click', () => {
                document.querySelector(hero.cta.link)?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    }

    renderExpertise() {
        const expertise = this.data.content.expertise;
        const process = this.data.content.process;
        const about = this.data.content.about;
        const contact = this.data.content.contact;
        
        // Nom - utiliser le nom du profil ou de l'atelier
        const nameElement = document.getElementById('expert-name');
        if (nameElement) {
            let displayName = 'Mme Monod, Artisane Remmailleuse'; // Valeur par défaut
            
            if (about && about.profile && about.profile.name) {
                displayName = `${about.profile.name}, ${about.profile.title}`;
            } else if (contact && contact.addresses && contact.addresses[0]) {
                displayName = contact.addresses[0].label || 'Atelier Suisse';
            }
            
            nameElement.textContent = displayName;
        }
        
        // Image de profil
        const portraitImg = document.querySelector('.portrait img');
        const portraitAlt = document.querySelector('.portrait img');
        if (portraitImg && about && about.profile) {
            if (about.profile.image) {
                portraitImg.src = about.profile.image;
                portraitImg.alt = `Portrait de ${about.profile.name}`;
            }
        }
        
        // Description
        const descriptionElement = document.getElementById('expert-description');
        if (descriptionElement) {
            let description = '';
            
            if (about && about.profile && about.profile.bio) {
                description = about.profile.bio;
            } else if (expertise && expertise.intro) {
                if (Array.isArray(expertise.intro.description)) {
                    description = expertise.intro.description.join('</p><p>');
                } else {
                    description = expertise.intro.description;
                }
            }
            
            if (description) {
                descriptionElement.innerHTML = `<p>${description}</p>`;
            }
        }

        // Étapes du processus
        if (process && process.steps) {
            this.renderProcessSteps(process.steps);
        } else if (expertise && expertise.process) {
            this.renderProcessSteps(expertise.process);
        }
    }

    renderProcessSteps(steps) {
        const container = document.getElementById('process-steps');
        if (!container || !steps) return;

        container.innerHTML = steps.map(step => `
            <div class="step-card scroll-reveal" style="animation-delay: ${((step.step || step.number) - 1) * 0.2}s;">
                <span class="step-icon">${step.icon}</span>
                <h3 class="step-title">${step.step || step.number}. ${step.title}</h3>
                <p class="step-description">${step.description}</p>
            </div>
        `).join('');
    }

    renderGallery() {
        const gallery = this.data.gallery;
        if (!gallery) return;

        this.renderGalleryFilters(gallery.categories);
        this.renderGalleryItems(gallery.items);
    }

    renderGalleryFilters(categories) {
        const container = document.getElementById('gallery-filters');
        if (!container || !categories) return;

        container.innerHTML = categories.map(category => `
            <button class="filter-btn ${category.active ? 'active' : ''}" 
                    data-category="${category.id}">
                ${category.name}
            </button>
        `).join('');

        // Event listeners pour les filtres
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                this.handleGalleryFilter(e.target);
            }
        });
    }

    renderGalleryItems(items) {
        const container = document.getElementById('gallery-grid');
        if (!container || !items) return;

        container.innerHTML = items.map((item, index) => `
            <div class="gallery-item scroll-reveal" 
                 data-category="${item.category}"
                 data-item-id="${item.id}"
                 style="animation-delay: ${index * 0.1}s;">
                <div class="gallery-image">
                    ${item.images?.length > 0 ? 
                        `<img src="${item.images.find(img => img.type === 'before')?.url || item.images[0].url}" alt="${item.title} - avant" loading="lazy">` :
                        `<div style="font-size: 3rem;">${item.fallback_icon || '🧶'}</div>`
                    }
                    <div class="gallery-overlay">
                        <div class="gallery-overlay-text">
                            <div>AVANT → APRÈS</div>
                            <small>Cliquez pour voir</small>
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h4 class="gallery-title">${item.title}</h4>
                    <p class="gallery-description">${item.description}</p>
                </div>
            </div>
        `).join('');

        // Event listeners pour la modal
        container.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('.gallery-item');
            if (galleryItem) {
                const itemId = galleryItem.dataset.itemId;
                this.openGalleryModal(itemId);
            }
        });
    }

    handleGalleryFilter(button) {
        const category = button.dataset.category;
        
        // Mettre à jour les boutons
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Filtrer les éléments
        const items = document.querySelectorAll('.gallery-item');
        items.forEach((item, index) => {
            const itemCategory = item.dataset.category;
            const shouldShow = category === 'tous' || itemCategory === category;
            
            if (shouldShow) {
                item.style.display = 'block';
                item.style.animationDelay = `${index * 0.1}s`;
                item.classList.add('scroll-reveal');
            } else {
                item.style.display = 'none';
            }
        });

        // Re-trigger scroll reveal pour les éléments visibles
        this.setupScrollReveal();
    }

    openGalleryModal(itemId) {
        const item = this.data.gallery.items.find(i => i.id === itemId);
        if (!item) return;

        const modal = document.getElementById('gallery-modal');
        const title = document.getElementById('modal-title');
        const description = document.getElementById('modal-description');
        const imageBefore = document.getElementById('modal-image-before');
        const imageAfter = document.getElementById('modal-image-after');

        if (title) title.textContent = item.title;
        if (description) description.textContent = item.description;

        if (item.images && item.images.length > 0) {
            const beforeImage = item.images.find(img => img.type === 'before');
            const afterImage = item.images.find(img => img.type === 'after');
            if (imageBefore) imageBefore.src = beforeImage?.url || '';
            if (imageAfter) imageAfter.src = afterImage?.url || '';
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Setup image toggle
        this.setupImageToggle();
    }

    setupImageToggle() {
        const toggleBefore = document.getElementById('toggle-before');
        const toggleAfter = document.getElementById('toggle-after');
        const imageBefore = document.getElementById('modal-image-before');
        const imageAfter = document.getElementById('modal-image-after');

        const showBefore = () => {
            imageBefore.classList.add('active');
            imageAfter.classList.remove('active');
            toggleBefore.classList.add('active');
            toggleAfter.classList.remove('active');
        };

        const showAfter = () => {
            imageAfter.classList.add('active');
            imageBefore.classList.remove('active');
            toggleAfter.classList.add('active');
            toggleBefore.classList.remove('active');
        };

        toggleBefore.addEventListener('click', showBefore);
        toggleAfter.addEventListener('click', showAfter);

        // Commencer par l'image "avant"
        showBefore();
    }

    closeGalleryModal() {
        const modal = document.getElementById('gallery-modal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    renderServices() {
        const services = this.data.services?.services;
        if (!services) return;

        const container = document.getElementById('services-grid');
        if (!container) return;

        container.innerHTML = services.map((service, index) => `
            <div class="service-card scroll-reveal" style="animation-delay: ${index * 0.1}s;">
                <span class="service-icon">${service.icon}</span>
                <h3 class="service-title">${service.name}</h3>
                <p class="service-description">${service.description}</p>
                <div class="service-price">${service.price?.display || service.price}</div>
            </div>
        `).join('');
    }

    renderContact() {
        const contact = this.data.content.contact;
        if (!contact) return;

        const container = document.getElementById('contact-info');
        if (!container) return;

        let html = '<h3 class="form-title">Coordonnées</h3>';

        // Adresses
        if (contact.addresses) {
            contact.addresses.forEach(address => {
                html += `
                    <div class="contact-item">
                        <div class="contact-icon">${address.country || '📍'}</div>
                        <div>
                            <strong>${address.title || address.label}</strong><br>
                            ${address.address}<br>
                            ${address.city || `${address.postal_code} ${address.city}`}
                        </div>
                    </div>
                `;
            });
        }

        // Téléphones
        if (contact.phones) {
            html += `
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div>
                        ${Array.isArray(contact.phones) 
                            ? contact.phones.map(phone => typeof phone === 'string' ? phone : phone.number).join('<br>')
                            : contact.phones
                        }
                    </div>
                </div>
            `;
        }

        // Délais
        if (contact.delays) {
            html += `
                <div class="contact-item">
                    <div class="contact-icon">⏰</div>
                    <div>
                        <strong>Délais</strong><br>
                        ${contact.delays}
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    renderFooter() {
        const footerTitle = document.getElementById('footer-title');
        const footerDescription = document.getElementById('footer-description');

        if (footerTitle) footerTitle.textContent = this.data.content.site?.name || 'Remmailleuse';
        if (footerDescription) {
            footerDescription.textContent = this.data.content.site?.description || 
                'L\'art traditionnel du remaillage pour redonner vie à vos tissus précieux.';
        }
    }

    // ===== EVENT LISTENERS =====
    setupEventListeners() {
        // Modal gallery
        const modalClose = document.getElementById('modal-close');
        const modalOverlay = document.getElementById('modal-overlay');
        
        if (modalClose) modalClose.addEventListener('click', () => this.closeGalleryModal());
        if (modalOverlay) modalOverlay.addEventListener('click', () => this.closeGalleryModal());

        // Escape key pour fermer la modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeGalleryModal();
            }
        });

        // Calculateur de prix
        const priceForm = document.getElementById('price-calculator-form');
        if (priceForm) {
            priceForm.addEventListener('submit', (e) => this.handlePriceCalculator(e));
        }

        // Formulaire de contact
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleContactForm(e));
        }

        // Upload de fichiers
        this.setupFileUpload();

        // Navbar scroll effect
        window.addEventListener('scroll', () => this.handleNavbarScroll());

        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => this.toggleMobileMenu());
        }
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', () => this.closeMobileMenu());
        }
        
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', () => this.closeMobileMenu());
        }
        
        // Fermer le menu mobile lors du clic sur un lien
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        });
        
        // Fermer le menu mobile avec la touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeMobileMenu();
            }
        });
        
        // Fermer le menu mobile lors du redimensionnement vers desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                this.closeMobileMenu();
            }
        });
    }

    setupNavigation() {
        // Smooth scroll pour tous les liens d'ancre
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    setupScrollReveal() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        // Observer tous les éléments scroll-reveal
        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });
    }


    setupFileUpload() {
        const fileUpload = document.getElementById('file-upload');
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');

        if (!fileUpload || !fileInput || !filePreview) return;

        fileUpload.addEventListener('click', () => fileInput.click());

        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });

        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            const files = e.dataTransfer.files;
            this.handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
    }

    handleFiles(files) {
        const filePreview = document.getElementById('file-preview');
        const maxFiles = 5;
        const maxSize = 5 * 1024 * 1024; // 5MB

        const validFiles = Array.from(files).slice(0, maxFiles).filter(file => {
            if (file.size > maxSize) {
                this.showToast(`❌ ${file.name} est trop volumineux (max 5MB)`, 'error');
                return false;
            }
            if (!file.type.startsWith('image/')) {
                this.showToast(`❌ ${file.name} n'est pas une image`, 'error');
                return false;
            }
            return true;
        });

        filePreview.innerHTML = '';

        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'file-preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button class="file-remove" onclick="this.parentElement.remove()">×</button>
                `;
                filePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });

        if (validFiles.length > 0) {
            this.showToast(`📷 ${validFiles.length} image(s) ajoutée(s)`, 'success');
        }
    }

    // ===== HANDLERS =====
    handlePriceCalculator(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const garmentType = formData.get('garment-type') || document.getElementById('garment-type').value;
        const damageType = formData.get('damage-type') || document.getElementById('damage-type').value;
        const damageSize = formData.get('damage-size') || document.getElementById('damage-size').value;

        if (!garmentType || !damageType) {
            this.showToast('⚠️ Veuillez remplir tous les champs obligatoires', 'warning');
            return;
        }

        // Logique de calcul basique
        let basePrice = 20;
        const multipliers = {
            'pull': 1.2,
            'bas': 0.8,
            'robe': 1.5,
            'autre': 1.0
        };

        const damageMultipliers = {
            'mite': 1.3,
            'accroc': 1.0,
            'usure': 1.5,
            'autre': 1.2
        };

        basePrice *= (multipliers[garmentType] || 1.0);
        basePrice *= (damageMultipliers[damageType] || 1.0);

        if (damageSize) {
            const size = parseFloat(damageSize);
            if (size > 5) basePrice *= 1.5;
            if (size > 10) basePrice *= 2;
        }

        const estimatedPrice = Math.round(basePrice);
        const priceRange = `${estimatedPrice - 5}-${estimatedPrice + 10}€`;

        const resultElement = document.getElementById('estimate-result');
        const priceElement = resultElement.querySelector('.estimate-price');
        
        if (priceElement) priceElement.textContent = `Estimation : ${priceRange}`;
        resultElement.style.display = 'block';

        this.showToast('💰 Estimation calculée !', 'success');
    }

    async handleContactForm(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('contact-submit');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');

        // UI Loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';

        try {
            const formData = new FormData(e.target);
            
            // Validation basique
            const required = ['firstname', 'lastname', 'email', 'message'];
            for (const field of required) {
                if (!formData.get(field)) {
                    throw new Error(`Le champ ${field} est obligatoire`);
                }
            }

            // Simulation d'envoi (à remplacer par l'API réelle)
            await this.simulateFormSubmission(formData);
            
            // Succès
            this.showToast('✅ Votre demande a été envoyée avec succès !', 'success');
            e.target.reset();
            document.getElementById('file-preview').innerHTML = '';
            
        } catch (error) {
            // Erreur envoi formulaire
            this.showToast(`❌ Erreur : ${error.message}`, 'error');
        } finally {
            // Reset UI
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    }

    async simulateFormSubmission(formData) {
        // Simulation d'un délai d'envoi
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Dans un vrai projet, on enverrait à une API
        // const response = await fetch('/api/contact', {
        //     method: 'POST',
        //     body: formData
        // });
        // 
        // if (!response.ok) throw new Error('Erreur serveur');
        // 
        // return response.json();
        
        // Formulaire soumis
        return { success: true };
    }

    handleNavbarScroll() {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;

        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = 'none';
        }
    }

    toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const body = document.body;
        
        if (mobileMenu && mobileMenuBtn && mobileMenuOverlay) {
            const isOpen = mobileMenu.classList.contains('active');
            
            // Vibration tactile si supportée
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
            
            if (isOpen) {
                // Fermer le menu
                mobileMenu.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                body.style.overflow = '';
                
                // Réinitialiser les animations des items
                setTimeout(() => {
                    const menuItems = mobileMenu.querySelectorAll('.mobile-menu-items li');
                    menuItems.forEach(item => {
                        item.style.animation = 'none';
                        item.style.transform = 'translateX(50px)';
                        item.style.opacity = '0';
                    });
                }, 400);
            } else {
                // Ouvrir le menu
                mobileMenu.classList.add('active');
                mobileMenuBtn.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                body.style.overflow = 'hidden';
                
                // Relancer les animations des items
                setTimeout(() => {
                    const menuItems = mobileMenu.querySelectorAll('.mobile-menu-items li');
                    menuItems.forEach((item, index) => {
                        item.style.animation = `slideInLeft 0.4s ease forwards`;
                        item.style.animationDelay = `${(index + 1) * 0.1}s`;
                    });
                }, 50);
            }
        }
    }
    
    closeMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const body = document.body;
        
        if (mobileMenu && mobileMenuBtn && mobileMenuOverlay) {
            mobileMenu.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            body.style.overflow = '';
        }
    }

    // ===== UTILITAIRES =====
    showLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
        }
    }

    hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
    }

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastIcon = toast.querySelector('.toast-icon');
        const toastMessage = toast.querySelector('.toast-message');

        // Icônes selon le type
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        toastIcon.textContent = icons[type] || icons.success;
        toastMessage.textContent = message;
        
        toast.className = `toast ${type}`;
        toast.classList.add('show');

        // Auto-hide après 4 secondes
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // ===== MÉTHODES PUBLIQUES =====
    updateContent(newData) {
        this.data = { ...this.data, ...newData };
        this.renderContent();
        this.showToast('🔄 Contenu mis à jour !', 'success');
    }

    exportData() {
        const dataStr = JSON.stringify(this.data, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'remmailleuse-data.json';
        link.click();
        URL.revokeObjectURL(url);
        this.showToast('📤 Données exportées !', 'success');
    }
}

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
    window.remmailleuseApp = new RemmailleuseApp();
});

// ===== GESTION DES ERREURS GLOBALES =====
window.addEventListener('error', (e) => {
    // Erreur JavaScript
    if (window.remmailleuseApp) {
        window.remmailleuseApp.showToast('❌ Une erreur est survenue', 'error');
    }
});

window.addEventListener('unhandledrejection', (e) => {
    // Promise rejetée
    if (window.remmailleuseApp) {
        window.remmailleuseApp.showToast('❌ Erreur de chargement', 'error');
    }
});