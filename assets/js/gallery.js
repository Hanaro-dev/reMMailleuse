/**
 * Module Galerie - Remmailleuse
 * Gestion de la galerie interactive avec modal avant/après
 */

class GalleryManager {
    constructor(app) {
        this.app = app;
        this.currentItem = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Modal gallery
        const modalClose = document.getElementById('modal-close');
        const modalOverlay = document.getElementById('modal-overlay');
        
        if (modalClose) modalClose.addEventListener('click', () => this.closeModal());
        if (modalOverlay) modalOverlay.addEventListener('click', () => this.closeModal());

        // Escape key pour fermer la modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    render(galleryData) {
        if (!galleryData) return;
        
        this.renderFilters(galleryData.categories);
        this.renderItems(galleryData.items);
    }

    renderFilters(categories) {
        const container = document.getElementById('gallery-filters');
        if (!container || !categories) return;

        const filtersHtml = categories.map(category => `
            <button class="filter-btn ${category.active ? 'active' : ''}" 
                    data-category="${category.id}">
                ${category.name}
            </button>
        `).join('');
        HTMLSanitizer.setHTML(container, filtersHtml);

        // Event listeners pour les filtres
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                this.handleFilter(e.target);
            }
        });
    }

    renderItems(items) {
        const container = document.getElementById('gallery-grid');
        if (!container || !items) return;

        const itemsHtml = items.map((item, index) => `
            <div class="gallery-item scroll-reveal" 
                 data-category="${item.category}"
                 data-item-id="${item.id}"
                 style="animation-delay: ${index * 0.1}s;">
                <div class="gallery-image">
                    ${item.images?.before ? 
                        `<img src="${item.images.before}" alt="${item.title} - avant" loading="lazy">` :
                        `<div style="font-size: 3rem;">${item.icon}</div>`
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
        HTMLSanitizer.setHTML(container, itemsHtml);

        // Event listeners pour la modal
        container.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('.gallery-item');
            if (galleryItem) {
                const itemId = galleryItem.dataset.itemId;
                this.openModal(itemId);
            }
        });
    }

    handleFilter(button) {
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
        if (this.app && this.app.setupScrollReveal) {
            this.app.setupScrollReveal();
        }
    }

    openModal(itemId) {
        const item = this.app.data.gallery.items.find(i => i.id === itemId);
        if (!item) return;

        this.currentItem = item;

        const modal = document.getElementById('gallery-modal');
        const title = document.getElementById('modal-title');
        const description = document.getElementById('modal-description');
        const imageBefore = document.getElementById('modal-image-before');
        const imageAfter = document.getElementById('modal-image-after');

        if (title) title.textContent = item.title;
        if (description) description.textContent = item.description;

        if (item.images) {
            if (imageBefore) imageBefore.src = item.images.before || '';
            if (imageAfter) imageAfter.src = item.images.after || '';
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
            if (imageBefore) imageBefore.classList.add('active');
            if (imageAfter) imageAfter.classList.remove('active');
            if (toggleBefore) toggleBefore.classList.add('active');
            if (toggleAfter) toggleAfter.classList.remove('active');
        };

        const showAfter = () => {
            if (imageAfter) imageAfter.classList.add('active');
            if (imageBefore) imageBefore.classList.remove('active');
            if (toggleAfter) toggleAfter.classList.add('active');
            if (toggleBefore) toggleBefore.classList.remove('active');
        };

        if (toggleBefore) toggleBefore.addEventListener('click', showBefore);
        if (toggleAfter) toggleAfter.addEventListener('click', showAfter);

        // Commencer par l'image "avant"
        showBefore();
    }

    closeModal() {
        const modal = document.getElementById('gallery-modal');
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
        this.currentItem = null;
    }

    // Méthodes publiques pour l'API
    addItem(item) {
        if (!this.app.data.gallery) this.app.data.gallery = { items: [] };
        this.app.data.gallery.items.push(item);
        this.render(this.app.data.gallery);
    }

    removeItem(itemId) {
        if (!this.app.data.gallery?.items) return;
        this.app.data.gallery.items = this.app.data.gallery.items.filter(item => item.id !== itemId);
        this.render(this.app.data.gallery);
    }

    updateItem(itemId, newData) {
        if (!this.app.data.gallery?.items) return;
        const index = this.app.data.gallery.items.findIndex(item => item.id === itemId);
        if (index !== -1) {
            this.app.data.gallery.items[index] = { ...this.app.data.gallery.items[index], ...newData };
            this.render(this.app.data.gallery);
        }
    }
}

// Export pour utilisation externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GalleryManager;
}