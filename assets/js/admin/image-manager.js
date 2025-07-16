/**
 * Gestionnaire d'images pour l'interface admin
 * Gère l'upload, la compression et l'affichage des images
 */

class AdminImageManager {
    constructor(dataManager) {
        this.dataManager = dataManager;
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        this.compressionQuality = 0.8;
        this.maxWidth = 1200;
        this.maxHeight = 800;
        this.currentImageType = 'before'; // 'before' ou 'after'
        this.setupEventListeners();
    }

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Gestion du drag & drop global
        this.setupGlobalDragDrop();
    }

    /**
     * Configuration du drag & drop global
     */
    setupGlobalDragDrop() {
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            const files = Array.from(e.dataTransfer.files);
            const imageFiles = files.filter(file => this.isValidImageFile(file));
            
            if (imageFiles.length > 0) {
                // Déterminer le contexte et traiter les images
                const target = e.target.closest('.gallery-item-admin');
                if (target) {
                    const index = parseInt(target.dataset.itemIndex);
                    this.handleImageUpload(index, { files: imageFiles });
                }
            }
        });
    }

    /**
     * Gestion de l'upload d'image
     */
    async handleImageUpload(itemIndex, input) {
        const files = input.files || input;
        if (!files || files.length === 0) return;

        const file = files[0];
        
        try {
            // Validation du fichier
            this.validateImageFile(file);
            
            // Compression et traitement
            const processedImage = await this.processImage(file);
            
            // Mise à jour des données
            this.updateGalleryItemImage(itemIndex, processedImage);
            
            // Mise à jour de l'affichage
            this.updateImagePreview(itemIndex, processedImage.dataUrl);
            
            // Notification de succès
            if (window.adminApp && window.adminApp.uiManager) {
                window.adminApp.uiManager.showNotification('📷 Image mise à jour avec succès !', 'success');
            }
            
        } catch (error) {
            console.error('Erreur lors du traitement de l\'image:', error);
            if (window.adminApp && window.adminApp.uiManager) {
                window.adminApp.uiManager.showNotification(`❌ ${error.message}`, 'error');
            }
        }
    }

    /**
     * Validation d'un fichier image
     */
    validateImageFile(file) {
        if (!this.isValidImageFile(file)) {
            throw new Error('Type de fichier non supporté. Utilisez JPG, PNG ou WebP.');
        }

        if (file.size > this.maxFileSize) {
            throw new Error(`Fichier trop volumineux (max ${Math.round(this.maxFileSize / 1024 / 1024)}MB)`);
        }

        return true;
    }

    /**
     * Vérification si le fichier est une image valide
     */
    isValidImageFile(file) {
        return file && this.allowedTypes.includes(file.type);
    }

    /**
     * Traitement et compression d'image
     */
    async processImage(file) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                try {
                    // Calcul des dimensions optimales
                    const dimensions = this.calculateOptimalDimensions(img.width, img.height);
                    
                    // Configuration du canvas
                    canvas.width = dimensions.width;
                    canvas.height = dimensions.height;
                    
                    // Dessin de l'image redimensionnée
                    ctx.drawImage(img, 0, 0, dimensions.width, dimensions.height);
                    
                    // Compression et export
                    const compressedDataUrl = canvas.toDataURL('image/jpeg', this.compressionQuality);
                    
                    resolve({
                        dataUrl: compressedDataUrl,
                        originalSize: file.size,
                        compressedSize: Math.round(compressedDataUrl.length * 0.75),
                        dimensions: dimensions,
                        filename: file.name,
                        type: this.currentImageType
                    });
                    
                } catch (error) {
                    reject(new Error('Erreur lors du traitement de l\'image'));
                }
            };
            
            img.onerror = () => reject(new Error('Impossible de charger l\'image'));
            img.src = URL.createObjectURL(file);
        });
    }

    /**
     * Calcul des dimensions optimales
     */
    calculateOptimalDimensions(originalWidth, originalHeight) {
        let { width, height } = { width: originalWidth, height: originalHeight };
        
        // Redimensionner si trop large
        if (width > this.maxWidth) {
            height = (height * this.maxWidth) / width;
            width = this.maxWidth;
        }
        
        // Redimensionner si trop haut
        if (height > this.maxHeight) {
            width = (width * this.maxHeight) / height;
            height = this.maxHeight;
        }
        
        return {
            width: Math.round(width),
            height: Math.round(height)
        };
    }

    /**
     * Mise à jour de l'image dans les données
     */
    updateGalleryItemImage(itemIndex, imageData) {
        const galleryItems = this.dataManager.getData('gallery', 'items') || [];
        
        if (itemIndex >= 0 && itemIndex < galleryItems.length) {
            const item = galleryItems[itemIndex];
            
            // Initialiser l'objet images s'il n'existe pas
            if (!item.images) {
                item.images = {};
            }
            
            // Sauvegarder l'image selon le type (before/after)
            item.images[this.currentImageType] = imageData.dataUrl;
            
            // Mise à jour des métadonnées
            if (!item.metadata) {
                item.metadata = {};
            }
            item.metadata[`${this.currentImageType}ImageInfo`] = {
                filename: imageData.filename,
                originalSize: imageData.originalSize,
                compressedSize: imageData.compressedSize,
                dimensions: imageData.dimensions,
                uploadDate: new Date().toISOString()
            };
            
            // Marquer comme modifié
            this.dataManager.updateGalleryItem(itemIndex, 'images', item.images);
            this.dataManager.updateGalleryItem(itemIndex, 'metadata', item.metadata);
            
            return true;
        }
        
        return false;
    }

    /**
     * Mise à jour de l'aperçu d'image
     */
    updateImagePreview(itemIndex, dataUrl) {
        const galleryContainer = document.getElementById('gallery-admin');
        if (!galleryContainer) return;
        
        const itemElement = galleryContainer.querySelector(`[data-item-index="${itemIndex}"]`);
        if (!itemElement) return;
        
        const previewContainer = itemElement.querySelector('.gallery-preview');
        if (!previewContainer) return;
        
        // Mise à jour ou création de l'image de prévisualisation
        let img = previewContainer.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = 'Aperçu';
            img.loading = 'lazy';
            
            // Remplacer le contenu existant
            const placeholder = previewContainer.querySelector('.gallery-placeholder');
            if (placeholder) {
                placeholder.remove();
            }
            
            previewContainer.insertBefore(img, previewContainer.firstChild);
        }
        
        img.src = dataUrl;
        
        // Ajouter un indicateur de type d'image
        this.updateImageTypeIndicator(previewContainer);
    }

    /**
     * Mise à jour de l'indicateur de type d'image
     */
    updateImageTypeIndicator(previewContainer) {
        let indicator = previewContainer.querySelector('.image-type-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'image-type-indicator';
            previewContainer.appendChild(indicator);
        }
        
        indicator.textContent = this.currentImageType === 'before' ? 'Avant' : 'Après';
        indicator.className = `image-type-indicator ${this.currentImageType}`;
    }

    /**
     * Sélection du type d'image (avant/après)
     */
    selectImageType(itemIndex, type) {
        this.currentImageType = type;
        
        // Déclencher la sélection de fichier
        const galleryContainer = document.getElementById('gallery-admin');
        if (!galleryContainer) return;
        
        const itemElement = galleryContainer.querySelector(`[data-item-index="${itemIndex}"]`);
        if (!itemElement) return;
        
        const fileInput = itemElement.querySelector('.gallery-file-input');
        if (fileInput) {
            // Stocker l'index et le type pour le traitement
            fileInput.dataset.itemIndex = itemIndex;
            fileInput.dataset.imageType = type;
            fileInput.click();
        }
    }

    /**
     * Création d'un aperçu d'image avec contrôles
     */
    createImagePreview(dataUrl, container, options = {}) {
        const {
            removable = true,
            className = 'image-preview',
            onRemove = null,
            showInfo = false
        } = options;
        
        const preview = document.createElement('div');
        preview.className = className;
        
        // Image
        const img = document.createElement('img');
        img.src = dataUrl;
        img.alt = 'Aperçu';
        img.loading = 'lazy';
        preview.appendChild(img);
        
        // Bouton de suppression
        if (removable) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'image-remove-btn';
            removeBtn.textContent = '×';
            removeBtn.title = 'Supprimer cette image';
            removeBtn.onclick = () => {
                preview.remove();
                if (onRemove) onRemove();
            };
            preview.appendChild(removeBtn);
        }
        
        // Informations sur l'image
        if (showInfo) {
            const info = document.createElement('div');
            info.className = 'image-info';
            info.innerHTML = `
                <small>
                    Type: ${this.currentImageType}<br>
                    Taille: ${this.formatFileSize(dataUrl.length * 0.75)}
                </small>
            `;
            preview.appendChild(info);
        }
        
        // Contrôles avant/après
        const controls = document.createElement('div');
        controls.className = 'image-controls';
        controls.innerHTML = `
            <button type="button" class="btn-toggle ${this.currentImageType === 'before' ? 'active' : ''}" 
                    data-type="before">Avant</button>
            <button type="button" class="btn-toggle ${this.currentImageType === 'after' ? 'active' : ''}" 
                    data-type="after">Après</button>
        `;
        preview.appendChild(controls);
        
        container.appendChild(preview);
        return preview;
    }

    /**
     * Suppression d'une image
     */
    removeImage(itemIndex, imageType = null) {
        const type = imageType || this.currentImageType;
        const galleryItems = this.dataManager.getData('gallery', 'items') || [];
        
        if (itemIndex >= 0 && itemIndex < galleryItems.length) {
            const item = galleryItems[itemIndex];
            
            if (item.images && item.images[type]) {
                delete item.images[type];
                
                // Nettoyer les métadonnées aussi
                if (item.metadata && item.metadata[`${type}ImageInfo`]) {
                    delete item.metadata[`${type}ImageInfo`];
                }
                
                // Mise à jour
                this.dataManager.updateGalleryItem(itemIndex, 'images', item.images);
                this.dataManager.updateGalleryItem(itemIndex, 'metadata', item.metadata);
                
                // Mise à jour de l'affichage
                this.updateImagePreviewAfterRemoval(itemIndex, type);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mise à jour de l'affichage après suppression
     */
    updateImagePreviewAfterRemoval(itemIndex, imageType) {
        const galleryContainer = document.getElementById('gallery-admin');
        if (!galleryContainer) return;
        
        const itemElement = galleryContainer.querySelector(`[data-item-index="${itemIndex}"]`);
        if (!itemElement) return;
        
        const previewContainer = itemElement.querySelector('.gallery-preview');
        if (!previewContainer) return;
        
        const img = previewContainer.querySelector('img');
        const galleryItems = this.dataManager.getData('gallery', 'items') || [];
        const item = galleryItems[itemIndex];
        
        // Vérifier s'il reste des images
        const hasImages = item.images && (item.images.before || item.images.after);
        
        if (!hasImages) {
            // Revenir au placeholder
            if (img) img.remove();
            
            const placeholder = document.createElement('div');
            placeholder.className = 'gallery-placeholder';
            placeholder.style.fontSize = '3rem';
            placeholder.textContent = item.icon || '📷';
            previewContainer.insertBefore(placeholder, previewContainer.firstChild);
        } else if (imageType === this.currentImageType) {
            // Basculer vers l'autre image si elle existe
            const otherType = imageType === 'before' ? 'after' : 'before';
            if (item.images[otherType]) {
                img.src = item.images[otherType];
                this.currentImageType = otherType;
                this.updateImageTypeIndicator(previewContainer);
            }
        }
    }

    /**
     * Formatage de la taille de fichier
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Optimisation des images en lot
     */
    async optimizeAllImages() {
        const galleryItems = this.dataManager.getData('gallery', 'items') || [];
        let optimizedCount = 0;
        
        for (let i = 0; i < galleryItems.length; i++) {
            const item = galleryItems[i];
            if (item.images) {
                for (const [type, dataUrl] of Object.entries(item.images)) {
                    if (this.needsOptimization(dataUrl)) {
                        try {
                            const optimized = await this.recompressImage(dataUrl);
                            item.images[type] = optimized.dataUrl;
                            optimizedCount++;
                        } catch (error) {
                            console.warn(`Erreur d'optimisation pour l'item ${i}, type ${type}:`, error);
                        }
                    }
                }
            }
        }
        
        if (optimizedCount > 0) {
            // Sauvegarder les changements
            this.dataManager.updateData('gallery', 'items', galleryItems);
            
            if (window.adminApp && window.adminApp.uiManager) {
                window.adminApp.uiManager.showNotification(
                    `✅ ${optimizedCount} image(s) optimisée(s)`,
                    'success'
                );
            }
        }
        
        return optimizedCount;
    }

    /**
     * Vérification si une image a besoin d'optimisation
     */
    needsOptimization(dataUrl) {
        // Estimation basée sur la taille de la chaîne base64
        const sizeEstimate = dataUrl.length * 0.75;
        return sizeEstimate > (1024 * 1024); // Plus de 1MB
    }

    /**
     * Recompression d'une image existante
     */
    async recompressImage(dataUrl) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                const dimensions = this.calculateOptimalDimensions(img.width, img.height);
                canvas.width = dimensions.width;
                canvas.height = dimensions.height;
                
                ctx.drawImage(img, 0, 0, dimensions.width, dimensions.height);
                
                const compressedDataUrl = canvas.toDataURL('image/jpeg', this.compressionQuality);
                
                resolve({
                    dataUrl: compressedDataUrl,
                    originalSize: dataUrl.length * 0.75,
                    compressedSize: compressedDataUrl.length * 0.75,
                    dimensions: dimensions
                });
            };
            
            img.onerror = () => reject(new Error('Erreur lors du rechargement de l\'image'));
            img.src = dataUrl;
        });
    }

    /**
     * Nettoyage lors de la destruction
     */
    destroy() {
        this.currentImageType = 'before';
    }
}

// Export pour utilisation dans d'autres modules
window.AdminImageManager = AdminImageManager;