/**
 * Composants UI pour le site ReMmailleuse
 * Gestion des rendus et interactions des différentes sections
 */

class UIComponents {
    constructor(dataManager) {
        this.dataManager = dataManager;
        this.setupEventListeners();
    }

    /**
     * Configure les événements généraux
     */
    setupEventListeners() {
        // Gestion des états de chargement
        this.setupLoadingStates();
        
        // Gestion des toasts
        this.setupToastSystem();
    }

    /**
     * Affiche/masque l'overlay de chargement
     */
    setupLoadingStates() {
        this.loadingOverlay = document.getElementById('loading-overlay');
    }

    showLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
        }
    }

    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
        }
    }

    /**
     * Système de notifications toast
     */
    setupToastSystem() {
        this.toast = document.getElementById('toast');
    }

    showToast(message, type = 'info') {
        if (!this.toast) return;

        const toastIcon = this.toast.querySelector('.toast-icon');
        const toastMessage = this.toast.querySelector('.toast-message');

        // Icônes selon le type
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        toastIcon.textContent = icons[type] || icons.info;
        toastMessage.textContent = message;

        // Afficher le toast
        this.toast.classList.add('show');
        this.toast.classList.remove('success', 'error', 'warning', 'info');
        this.toast.classList.add(type);

        // Masquer après 3 secondes
        setTimeout(() => {
            this.toast.classList.remove('show');
        }, 3000);
    }

    /**
     * Rendu de la section Hero
     */
    renderHero() {
        const hero = this.dataManager.get('content', 'hero');
        if (!hero) return;

        const titleElement = document.getElementById('hero-title');
        const subtitleElement = document.getElementById('hero-subtitle');
        const ctaElement = document.getElementById('hero-cta');
        const ctaTextElement = document.getElementById('hero-cta-text');

        if (titleElement) {
            const safeTitle = hero.title.includes('redonner vie') 
                ? hero.title.replace('redonner vie', '<span class="text-gradient">redonner vie</span>')
                : hero.title;
            window.safeHTML(titleElement, safeTitle);
        }
        if (subtitleElement) subtitleElement.textContent = hero.subtitle;
        if (ctaTextElement) ctaTextElement.textContent = hero.cta.text;
        if (ctaElement && hero.cta.link) {
            ctaElement.onclick = () => {
                document.querySelector(hero.cta.link)?.scrollIntoView({ behavior: 'smooth' });
            };
        }
    }

    /**
     * Rendu de la section Expertise
     */
    renderExpertise() {
        const expertise = this.dataManager.get('content', 'expertise');
        if (!expertise) return;

        // Informations de l'expert
        const expertNameElement = document.getElementById('expert-name');
        const expertDescriptionElement = document.getElementById('expert-description');

        if (expertNameElement && expertise.intro?.name) {
            expertNameElement.textContent = expertise.intro.name;
        }

        if (expertDescriptionElement && expertise.intro?.description) {
            const description = Array.isArray(expertise.intro.description) 
                ? expertise.intro.description.join('</p><p>') 
                : expertise.intro.description;
            
            if (description) {
                window.safeHTML(expertDescriptionElement, `<p>${description}</p>`);
            }
        }

        // Étapes du processus
        const process = this.dataManager.get('content', 'process');
        if (process?.steps) {
            this.renderProcessSteps(process.steps);
        } else if (expertise.process?.steps) {
            this.renderProcessSteps(expertise.process.steps);
        }
    }

    /**
     * Rendu des étapes du processus
     */
    renderProcessSteps(steps) {
        const container = document.getElementById('process-steps');
        if (!container || !steps) return;

        const stepsHTML = steps.map(step => `
            <div class="step-card scroll-reveal" style="animation-delay: ${((step.step || step.number) - 1) * 0.2}s;">
                <span class="step-icon">${step.icon}</span>
                <h3 class="step-title">${step.step || step.number}. ${step.title}</h3>
                <p class="step-description">${step.description}</p>
            </div>
        `).join('');
        window.safeHTML(container, stepsHTML);
    }

    /**
     * Rendu de la galerie
     */
    renderGallery() {
        const gallery = this.dataManager.get('gallery');
        if (!gallery) return;

        // Filtres
        if (gallery.categories) {
            this.renderGalleryFilters(gallery.categories);
        }

        // Items
        if (gallery.items) {
            this.renderGalleryItems(gallery.items);
        }
    }

    /**
     * Rendu des filtres de galerie
     */
    renderGalleryFilters(categories) {
        const container = document.getElementById('gallery-filters');
        if (!container || !categories) return;

        const categoriesHTML = categories.map(category => `
            <button class="filter-btn ${category.active ? 'active' : ''}" 
                    data-category="${category.id}">
                ${category.name}
            </button>
        `).join('');
        window.safeHTML(container, categoriesHTML);

        // Event listeners pour les filtres
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                // Mettre à jour les boutons actifs
                container.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');

                // Filtrer les items
                const category = e.target.dataset.category;
                this.filterGalleryItems(category);
            }
        });
    }

    /**
     * Rendu des items de galerie
     */
    renderGalleryItems(items) {
        const container = document.getElementById('gallery-grid');
        if (!container || !items) return;

        const itemsHTML = items.map((item, index) => `
            <div class="gallery-item scroll-reveal" 
                 data-category="${item.category}"
                 data-item-id="${item.id}"
                 style="animation-delay: ${index * 0.1}s;">
                <div class="gallery-image">
                    <img src="${item.images.before}" alt="${item.title} - Avant" class="image-before">
                    <img src="${item.images.after}" alt="${item.title} - Après" class="image-after">
                    <div class="gallery-overlay">
                        <span class="gallery-icon">🔍</span>
                        <span class="gallery-text">Voir les détails</span>
                    </div>
                </div>
                <div class="gallery-info">
                    <h4 class="gallery-title">${item.title}</h4>
                    <p class="gallery-description">${item.description}</p>
                </div>
            </div>
        `).join('');
        window.safeHTML(container, itemsHTML);

        // Event listeners pour la modal
        container.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('.gallery-item');
            if (galleryItem) {
                const itemId = galleryItem.dataset.itemId;
                const item = items.find(i => i.id === itemId);
                if (item) {
                    this.openGalleryModal(item);
                }
            }
        });
    }

    /**
     * Filtre les items de galerie
     */
    filterGalleryItems(category) {
        const items = document.querySelectorAll('.gallery-item');
        items.forEach(item => {
            const itemCategory = item.dataset.category;
            const shouldShow = category === 'tous' || itemCategory === category;
            item.style.display = shouldShow ? 'block' : 'none';
        });
    }

    /**
     * Ouvre la modal de galerie
     */
    openGalleryModal(item) {
        const modal = document.getElementById('gallery-modal');
        const imageBefore = document.getElementById('modal-image-before');
        const imageAfter = document.getElementById('modal-image-after');
        const title = document.getElementById('modal-title');
        const description = document.getElementById('modal-description');

        if (modal && imageBefore && imageAfter && title && description) {
            imageBefore.src = item.images.before;
            imageAfter.src = item.images.after;
            title.textContent = item.title;
            description.textContent = item.description;

            modal.style.display = 'flex';
            modal.classList.add('active');

            // Afficher l'image "avant" par défaut
            imageBefore.style.display = 'block';
            imageAfter.style.display = 'none';
        }
    }

    /**
     * Rendu des services
     */
    renderServices() {
        const services = this.dataManager.get('services', 'services');
        if (!services) return;

        const container = document.getElementById('services-grid');
        if (!container) return;

        const servicesHTML = services.map((service, index) => `
            <div class="service-card scroll-reveal" style="animation-delay: ${index * 0.1}s;">
                <span class="service-icon">${service.icon}</span>
                <h3 class="service-title">${service.name}</h3>
                <p class="service-description">${service.description}</p>
                <div class="service-price">${service.price?.display || service.price}</div>
            </div>
        `).join('');
        window.safeHTML(container, servicesHTML);
    }

    /**
     * Rendu des informations de contact
     */
    renderContact() {
        const contact = this.dataManager.get('content', 'contact');
        if (!contact) return;

        const container = document.getElementById('contact-info');
        if (!container) return;

        let html = '<div class="contact-sections">';

        // Adresses
        if (contact.addresses && contact.addresses.length > 0) {
            html += '<div class="contact-section"><h3>Adresses</h3>';
            contact.addresses.forEach(address => {
                const countryFlag = address.country === 'Suisse' ? '🇨🇭' : address.country;
                const title = address.label || address.title || address.type;
                const fullAddress = `${address.address}, ${address.postal_code} ${address.city}`;
                html += `
                    <div class="contact-address">
                        <span class="address-flag">${countryFlag}</span>
                        <div class="address-details">
                            <strong>${title}</strong>
                            <p>${fullAddress}</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        // Téléphones
        if (contact.phones && contact.phones.length > 0) {
            html += '<div class="contact-section"><h3>Téléphones</h3>';
            contact.phones.forEach(phone => {
                const phoneStr = typeof phone === 'string' ? phone : (phone.number || String(phone));
                const label = phone.label || phone.type || '';
                html += `<p>${label ? `<strong>${label}:</strong> ` : ''}<a href="tel:${phoneStr.replace(/\s/g, '')}">${phoneStr}</a></p>`;
            });
            html += '</div>';
        }

        // Email
        const emailStr = typeof contact.email === 'string' ? contact.email : contact.email?.primary;
        if (emailStr) {
            html += `<div class="contact-section"><h3>Email</h3><p><a href="mailto:${emailStr}">${emailStr}</a></p></div>`;
        }

        html += '</div>';
        window.safeHTML(container, html);
    }

    /**
     * Rendu de la galerie
     */
    renderGallery() {
        const galleryData = this.dataManager.get('gallery', 'items');
        if (!galleryData) return;

        // Rendu des filtres
        this.renderGalleryFilters();
        
        // Rendu des éléments
        this.renderGalleryItems(galleryData);
    }

    /**
     * Rendu des filtres de galerie
     */
    renderGalleryFilters() {
        const categories = this.dataManager.get('gallery', 'categories');
        if (!categories) return;

        const container = document.getElementById('gallery-filters');
        if (!container) return;

        const filtersHTML = categories.map(category => `
            <button class="filter-btn ${category.active ? 'active' : ''}" 
                    data-filter="${category.id}">
                ${category.name}
            </button>
        `).join('');
        
        window.safeHTML(container, filtersHTML);

        // Ajouter les événements de filtrage
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                // Retirer active de tous les boutons
                container.querySelectorAll('.filter-btn').forEach(btn => 
                    btn.classList.remove('active'));
                
                // Ajouter active au bouton cliqué
                e.target.classList.add('active');
                
                // Filtrer les éléments
                const filter = e.target.dataset.filter;
                this.filterGalleryItems(filter);
            }
        });
    }

    /**
     * Rendu des éléments de galerie
     */
    renderGalleryItems(items) {
        const container = document.getElementById('gallery-grid');
        if (!container) return;

        const itemsHTML = items.map((item, index) => {
            const mainImage = item.images && item.images.length > 0 ? item.images[0] : null;
            const fallbackIcon = item.fallback_icon || '📸';
            
            return `
                <div class="gallery-item scroll-reveal" 
                     data-category="${item.category}"
                     style="animation-delay: ${index * 0.1}s;">
                    <div class="gallery-image">
                        ${mainImage ? `
                            <img src="${mainImage.url}" 
                                 alt="${mainImage.alt}" 
                                 loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="image-fallback" style="display:none;">
                                <span class="fallback-icon">${fallbackIcon}</span>
                            </div>
                        ` : `
                            <div class="image-fallback">
                                <span class="fallback-icon">${fallbackIcon}</span>
                            </div>
                        `}
                        <div class="gallery-overlay">
                            <button class="gallery-view-btn" 
                                    onclick="window.app.openGalleryModal('${item.id}')">
                                📸 Voir
                            </button>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">${item.title}</h3>
                        <p class="gallery-description">${item.description}</p>
                        <div class="gallery-meta">
                            <span class="gallery-material">${item.material}</span>
                            <span class="gallery-duration">${item.duration}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        window.safeHTML(container, itemsHTML);
    }

    /**
     * Filtrage des éléments de galerie
     */
    filterGalleryItems(filter) {
        const items = document.querySelectorAll('.gallery-item');
        
        items.forEach(item => {
            const category = item.dataset.category;
            if (filter === 'tous' || category === filter) {
                item.style.display = 'block';
                item.classList.add('scroll-reveal');
            } else {
                item.style.display = 'none';
                item.classList.remove('scroll-reveal');
            }
        });
    }

    /**
     * Rendu de tous les composants
     */
    renderAll() {
        this.renderHero();
        this.renderExpertise();
        this.renderGallery();
        this.renderServices();
        this.renderContact();
    }
}

// Export global
window.UIComponents = UIComponents;