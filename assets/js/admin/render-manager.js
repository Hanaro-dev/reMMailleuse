/**
 * Gestionnaire de rendu pour l'interface admin
 * Gère le rendu dynamique des listes et composants
 */

class AdminRenderManager {
    constructor(dataManager) {
        this.dataManager = dataManager;
        this.renderCallbacks = new Map();
        this.setupRenderCallbacks();
    }

    /**
     * Configuration des callbacks de rendu
     */
    setupRenderCallbacks() {
        this.renderCallbacks.set('process-steps', () => this.renderProcessSteps());
        this.renderCallbacks.set('gallery-items', () => this.renderGalleryItems());
        this.renderCallbacks.set('service-items', () => this.renderServiceItems());
    }

    /**
     * Rendu de tous les éléments dynamiques
     */
    renderAll() {
        this.renderProcessSteps();
        this.renderGalleryItems();
        this.renderServiceItems();
    }

    /**
     * Rendu des étapes de processus
     */
    renderProcessSteps() {
        const container = document.getElementById('process-steps-admin');
        if (!container) return;

        const steps = this.dataManager.getData('content', 'expertise')?.process || [];
        
        const stepsHtml = steps.map((step, index) => `
            <div class="process-step-admin" data-step-index="${index}">
                <div class="step-icon-container">
                    <input type="text" class="step-icon-input" value="${this.escapeHtml(step.icon)}" placeholder="🔍" 
                           onchange="window.adminApp?.renderManager.updateProcessStep(${index}, 'icon', this.value)"
                           maxlength="2">
                </div>
                <div class="step-details">
                    <input type="text" class="form-input mb-1" value="${this.escapeHtml(step.title)}" placeholder="Titre de l'étape"
                           onchange="window.adminApp?.renderManager.updateProcessStep(${index}, 'title', this.value)"
                           maxlength="100">
                    <textarea class="form-input" rows="2" placeholder="Description de l'étape"
                              onchange="window.adminApp?.renderManager.updateProcessStep(${index}, 'description', this.value)"
                              maxlength="200">${this.escapeHtml(step.description)}</textarea>
                </div>
                <div class="step-controls">
                    <div class="step-number">${step.step}</div>
                    <button type="button" class="btn btn-error btn-sm" 
                            onclick="window.adminApp?.renderManager.removeProcessStep(${index})"
                            title="Supprimer cette étape">🗑️</button>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = stepsHtml;
    }

    /**
     * Rendu des éléments de galerie
     */
    renderGalleryItems() {
        const container = document.getElementById('gallery-admin');
        if (!container) return;

        const items = this.dataManager.getData('gallery', 'items') || [];
        const categories = this.dataManager.getData('gallery', 'categories') || [];
        
        const galleryHtml = `
            <div class="gallery-admin">
                ${items.map((item, index) => `
                    <div class="gallery-item-admin" data-item-index="${index}">
                        <div class="gallery-preview">
                            ${item.images?.before ? 
                                `<img src="${this.escapeHtml(item.images.before)}" alt="${this.escapeHtml(item.title)}" loading="lazy">` :
                                `<div class="gallery-placeholder" style="font-size: 3rem;">${this.escapeHtml(item.icon || '📷')}</div>`
                            }
                            <input type="file" accept="image/*" 
                                   onchange="window.adminApp?.imageManager.handleImageUpload(${index}, this)"
                                   class="gallery-file-input">
                        </div>
                        <div class="gallery-info">
                            <input type="text" class="form-input mb-1" value="${this.escapeHtml(item.title)}" 
                                   placeholder="Nom de la réalisation"
                                   onchange="window.adminApp?.renderManager.updateGalleryItem(${index}, 'title', this.value)"
                                   maxlength="100">
                            <textarea class="form-input mb-1" rows="2" placeholder="Description"
                                      onchange="window.adminApp?.renderManager.updateGalleryItem(${index}, 'description', this.value)"
                                      maxlength="200">${this.escapeHtml(item.description)}</textarea>
                            <select class="form-input mb-1" onchange="window.adminApp?.renderManager.updateGalleryItem(${index}, 'category', this.value)">
                                ${categories.map(cat => 
                                    `<option value="${this.escapeHtml(cat.id)}" ${item.category === cat.id ? 'selected' : ''}>${this.escapeHtml(cat.name)}</option>`
                                ).join('')}
                            </select>
                            <div class="gallery-actions">
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.adminApp?.imageManager.selectImageType(${index}, 'before')">📷 Photo avant</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.adminApp?.imageManager.selectImageType(${index}, 'after')">📷 Photo après</button>
                                <button type="button" class="btn btn-error btn-sm" onclick="window.adminApp?.renderManager.removeGalleryItem(${index})">🗑️</button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        container.innerHTML = galleryHtml;
    }

    /**
     * Rendu des services
     */
    renderServiceItems() {
        const container = document.getElementById('services-admin');
        if (!container) return;

        const services = this.dataManager.getData('services', 'services') || [];
        
        const servicesHtml = `
            <div class="services-admin">
                ${services.map((service, index) => `
                    <div class="service-item-admin" data-service-index="${index}">
                        <div class="service-icon-container">
                            <input type="text" class="service-icon-input" value="${this.escapeHtml(service.icon)}" placeholder="🧵"
                                   onchange="window.adminApp?.renderManager.updateService(${index}, 'icon', this.value)"
                                   maxlength="2">
                        </div>
                        <div class="service-details">
                            <input type="text" class="form-input mb-1" value="${this.escapeHtml(service.name)}" placeholder="Nom du service"
                                   onchange="window.adminApp?.renderManager.updateService(${index}, 'name', this.value)"
                                   maxlength="100">
                            <textarea class="form-input" rows="2" placeholder="Description du service"
                                      onchange="window.adminApp?.renderManager.updateService(${index}, 'description', this.value)"
                                      maxlength="200">${this.escapeHtml(service.description)}</textarea>
                        </div>
                        <div class="service-pricing">
                            <input type="text" class="form-input service-price-input text-center mb-2" 
                                   value="${this.escapeHtml(service.price)}" placeholder="Prix"
                                   onchange="window.adminApp?.renderManager.updateService(${index}, 'price', this.value)"
                                   maxlength="20">
                            <button type="button" class="btn btn-error btn-sm" 
                                    onclick="window.adminApp?.renderManager.removeService(${index})">🗑️ Supprimer</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        container.innerHTML = servicesHtml;
    }

    /**
     * Mise à jour d'une étape de processus
     */
    updateProcessStep(index, field, value) {
        if (this.dataManager.updateProcessStep(index, field, value)) {
            // Optionnel: re-render seulement cet élément pour optimiser
            this.renderProcessSteps();
            return true;
        }
        return false;
    }

    /**
     * Ajout d'une étape de processus
     */
    addProcessStep() {
        const newStep = this.dataManager.addProcessStep();
        if (newStep) {
            this.renderProcessSteps();
            return newStep;
        }
        return null;
    }

    /**
     * Suppression d'une étape de processus
     */
    removeProcessStep(index) {
        if (this.dataManager.removeProcessStep(index)) {
            this.renderProcessSteps();
            return true;
        }
        return false;
    }

    /**
     * Mise à jour d'un élément de galerie
     */
    updateGalleryItem(index, field, value) {
        if (this.dataManager.updateGalleryItem(index, field, value)) {
            // Re-render si nécessaire (par exemple pour les changements de catégorie)
            if (field === 'category') {
                this.renderGalleryItems();
            }
            return true;
        }
        return false;
    }

    /**
     * Ajout d'un élément de galerie
     */
    addGalleryItem() {
        const newItem = this.dataManager.addGalleryItem();
        if (newItem) {
            this.renderGalleryItems();
            return newItem;
        }
        return null;
    }

    /**
     * Suppression d'un élément de galerie
     */
    removeGalleryItem(index) {
        if (this.dataManager.removeGalleryItem(index)) {
            this.renderGalleryItems();
            return true;
        }
        return false;
    }

    /**
     * Mise à jour d'un service
     */
    updateService(index, field, value) {
        if (this.dataManager.updateService(index, field, value)) {
            return true;
        }
        return false;
    }

    /**
     * Ajout d'un service
     */
    addService() {
        const newService = this.dataManager.addService();
        if (newService) {
            this.renderServiceItems();
            return newService;
        }
        return null;
    }

    /**
     * Suppression d'un service
     */
    removeService(index) {
        if (this.dataManager.removeService(index)) {
            this.renderServiceItems();
            return true;
        }
        return false;
    }

    /**
     * Rendu conditionnel basé sur l'état
     */
    renderConditional(containerId, condition, renderFunction) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (condition) {
            renderFunction.call(this);
        } else {
            container.innerHTML = '<p class="no-data">Aucune donnée disponible</p>';
        }
    }

    /**
     * Rendu avec état de chargement
     */
    renderWithLoading(containerId, renderFunction) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Afficher l'état de chargement
        container.innerHTML = '<div class="loading-spinner">Chargement...</div>';

        // Simuler un délai pour l'UX (optionnel)
        setTimeout(() => {
            renderFunction.call(this);
        }, 100);
    }

    /**
     * Mise à jour partielle d'un élément
     */
    updateElement(containerId, index, field, value) {
        const container = document.getElementById(containerId);
        if (!container) return false;

        const element = container.querySelector(`[data-index="${index}"]`);
        if (!element) return false;

        const fieldElement = element.querySelector(`[data-field="${field}"]`);
        if (fieldElement) {
            if (fieldElement.tagName === 'INPUT' || fieldElement.tagName === 'TEXTAREA') {
                fieldElement.value = value;
            } else {
                fieldElement.textContent = value;
            }
            return true;
        }

        return false;
    }

    /**
     * Échappement HTML pour la sécurité
     */
    escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
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
     * Gestion des erreurs de rendu
     */
    handleRenderError(error, context = 'rendu') {
        console.error(`Erreur de ${context}:`, error);
        
        // Afficher un message d'erreur à l'utilisateur
        const errorMessage = `
            <div class="render-error">
                <p>❌ Erreur lors du ${context}</p>
                <button onclick="location.reload()" class="btn btn-primary btn-sm">Recharger la page</button>
            </div>
        `;
        
        return errorMessage;
    }

    /**
     * Optimisation du rendu avec debouncing
     */
    debounceRender(renderFunction, delay = 250) {
        if (this._renderTimeout) {
            clearTimeout(this._renderTimeout);
        }
        
        this._renderTimeout = setTimeout(() => {
            renderFunction.call(this);
        }, delay);
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        if (this._renderTimeout) {
            clearTimeout(this._renderTimeout);
        }
        this.renderCallbacks.clear();
    }
}

// Export pour utilisation dans d'autres modules
window.AdminRenderManager = AdminRenderManager;