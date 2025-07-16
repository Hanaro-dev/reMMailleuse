/**
 * Gestionnaire d'upload d'images avec prévisualisation et compression
 * Support du drag & drop, validation et feedback visuel
 */

class ImageUploadManager {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            maxFiles: 5,
            maxSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            uploadUrl: '/api/upload.php',
            previewContainer: null,
            onUploadStart: null,
            onUploadProgress: null,
            onUploadSuccess: null,
            onUploadError: null,
            ...options
        };
        
        this.files = [];
        this.uploading = false;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            return;
        }
        
        this.createUploadZone();
        this.setupEventListeners();
        this.createPreviewContainer();
    }
    
    createUploadZone() {
        const uploadZoneHtml = `
            <div class="upload-zone" id="upload-zone">
                <div class="upload-content">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">
                        <h3>Ajouter des photos</h3>
                        <p>Glissez vos images ici ou <span class="upload-link">cliquez pour parcourir</span></p>
                        <small>Maximum ${this.options.maxFiles} images • ${this.formatFileSize(this.options.maxSize)} max par image</small>
                    </div>
                </div>
                <input type="file" id="file-input" multiple accept="image/*" style="display: none;">
            </div>
        `;
        HTMLSanitizer.setHTML(this.container, uploadZoneHtml);
    }
    
    createPreviewContainer() {
        if (!this.options.previewContainer) {
            this.previewContainer = document.createElement('div');
            this.previewContainer.className = 'upload-previews';
            this.container.appendChild(this.previewContainer);
        } else {
            this.previewContainer = document.getElementById(this.options.previewContainer);
        }
    }
    
    setupEventListeners() {
        const uploadZone = this.container.querySelector('#upload-zone');
        const fileInput = this.container.querySelector('#file-input');
        
        // Clic sur la zone d'upload
        uploadZone.addEventListener('click', () => {
            if (!this.uploading) {
                fileInput.click();
            }
        });
        
        // Sélection de fichiers
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
        
        // Drag & Drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files).filter(file => 
                file.type.startsWith('image/')
            );
            
            this.handleFiles(files);
        });
    }
    
    handleFiles(files) {
        if (this.uploading) {
            return;
        }
        
        const fileArray = Array.from(files);
        
        // Vérifier le nombre total de fichiers
        if (this.files.length + fileArray.length > this.options.maxFiles) {
            this.showError(`Maximum ${this.options.maxFiles} images autorisées`);
            return;
        }
        
        // Valider chaque fichier
        const validFiles = [];
        for (const file of fileArray) {
            if (this.validateFile(file)) {
                validFiles.push(file);
            }
        }
        
        if (validFiles.length === 0) {
            return;
        }
        
        // Ajouter les fichiers valides
        validFiles.forEach(file => {
            this.files.push(file);
            this.createPreview(file);
        });
        
        this.updateUploadZone();
    }
    
    validateFile(file) {
        // Vérifier le type
        if (!this.options.allowedTypes.includes(file.type)) {
            this.showError(`Type de fichier non supporté: ${file.name}`);
            return false;
        }
        
        // Vérifier la taille
        if (file.size > this.options.maxSize) {
            this.showError(`Fichier trop volumineux: ${file.name} (max ${this.formatFileSize(this.options.maxSize)})`);
            return false;
        }
        
        return true;
    }
    
    createPreview(file) {
        const previewId = `preview-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        const previewElement = document.createElement('div');
        previewElement.className = 'upload-preview';
        previewElement.id = previewId;
        
        const previewHtml = `
            <div class="preview-image">
                <img src="" alt="${file.name}">
                <div class="preview-overlay">
                    <div class="preview-actions">
                        <button class="preview-remove" title="Supprimer">🗑️</button>
                    </div>
                </div>
            </div>
            <div class="preview-info">
                <div class="preview-name">${file.name}</div>
                <div class="preview-size">${this.formatFileSize(file.size)}</div>
                <div class="preview-status">En attente</div>
            </div>
        `;
        HTMLSanitizer.setHTML(previewElement, previewHtml);
        
        this.previewContainer.appendChild(previewElement);
        
        // Charger l'image
        const img = previewElement.querySelector('img');
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // Bouton de suppression
        const removeBtn = previewElement.querySelector('.preview-remove');
        removeBtn.addEventListener('click', () => {
            this.removeFile(file, previewId);
        });
    }
    
    removeFile(file, previewId) {
        // Supprimer de la liste
        this.files = this.files.filter(f => f !== file);
        
        // Supprimer la prévisualisation
        const previewElement = document.getElementById(previewId);
        if (previewElement) {
            previewElement.remove();
        }
        
        this.updateUploadZone();
    }
    
    updateUploadZone() {
        const uploadZone = this.container.querySelector('#upload-zone');
        const uploadText = uploadZone.querySelector('.upload-text h3');
        const uploadSubtext = uploadZone.querySelector('.upload-text p');
        
        if (this.files.length === 0) {
            uploadText.textContent = 'Ajouter des photos';
            HTMLSanitizer.setHTML(uploadSubtext, 'Glissez vos images ici ou <span class="upload-link">cliquez pour parcourir</span>');
            uploadZone.classList.remove('has-files');
        } else {
            uploadText.textContent = `${this.files.length} image${this.files.length > 1 ? 's' : ''} sélectionnée${this.files.length > 1 ? 's' : ''}`;
            HTMLSanitizer.setHTML(uploadSubtext, this.files.length < this.options.maxFiles 
                ? '<span class="upload-link">Ajouter d\'autres images</span>' 
                : 'Limite atteinte');
            uploadZone.classList.add('has-files');
        }
    }
    
    async uploadFiles() {
        if (this.files.length === 0) {
            this.showError('Aucune image à uploader');
            return null;
        }
        
        if (this.uploading) {
            return null;
        }
        
        this.uploading = true;
        
        // Callback de début d'upload
        if (this.options.onUploadStart) {
            this.options.onUploadStart(this.files);
        }
        
        try {
            // Préparer FormData
            const formData = new FormData();
            
            // Ajouter le token CSRF
            if (window.csrfManager && window.csrfManager.isReady()) {
                formData.append('csrf_token', window.csrfManager.getToken());
            }
            
            // Ajouter les fichiers
            this.files.forEach((file, index) => {
                formData.append(`images[${index}]`, file);
            });
            
            // Mettre à jour le statut des prévisualisations
            this.updatePreviewStatus('Envoi en cours...', 'uploading');
            
            // Envoyer la requête
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updatePreviewStatus('Envoyé avec succès', 'success');
                
                // Callback de succès
                if (this.options.onUploadSuccess) {
                    this.options.onUploadSuccess(result);
                }
                
                return result;
            } else {
                throw new Error(result.error || 'Erreur lors de l\'upload');
            }
            
        } catch (error) {
            this.updatePreviewStatus('Erreur d\'envoi', 'error');
            
            // Callback d'erreur
            if (this.options.onUploadError) {
                this.options.onUploadError(error);
            }
            
            this.showError('Erreur lors de l\'upload: ' + error.message);
            return null;
            
        } finally {
            this.uploading = false;
        }
    }
    
    updatePreviewStatus(status, className = '') {
        const statusElements = this.previewContainer.querySelectorAll('.preview-status');
        statusElements.forEach(element => {
            element.textContent = status;
            element.className = `preview-status ${className}`;
        });
    }
    
    clear() {
        this.files = [];
        this.previewContainer.textContent = '';
        this.updateUploadZone();
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
    
    showError(message) {
        // Créer ou trouver l'élément d'erreur
        let errorElement = this.container.querySelector('.upload-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'upload-error';
            this.container.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Masquer après 5 secondes
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 5000);
    }
    
    getFiles() {
        return this.files;
    }
    
    isUploading() {
        return this.uploading;
    }
}

// Styles CSS à ajouter
const uploadStyles = `
<style>
.upload-zone {
    border: 2px dashed #d0d0d0;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.upload-zone:hover {
    border-color: var(--color-primary);
    background: #f5f5f5;
}

.upload-zone.dragover {
    border-color: var(--color-primary);
    background: rgba(212, 137, 107, 0.1);
}

.upload-zone.has-files {
    border-color: var(--color-primary);
    background: rgba(212, 137, 107, 0.05);
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upload-icon {
    font-size: 3rem;
    opacity: 0.5;
}

.upload-text h3 {
    margin: 0;
    color: var(--color-dark);
    font-size: 1.2rem;
}

.upload-text p {
    margin: 0.5rem 0;
    color: var(--color-text-light);
}

.upload-link {
    color: var(--color-primary);
    text-decoration: underline;
}

.upload-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.upload-preview {
    position: relative;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

.preview-image {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.upload-preview:hover .preview-overlay {
    opacity: 1;
}

.preview-actions button {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.preview-actions button:hover {
    background: white;
    transform: scale(1.1);
}

.preview-info {
    padding: 0.75rem;
}

.preview-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--color-dark);
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-size {
    font-size: 0.8rem;
    color: var(--color-text-light);
    margin-bottom: 0.25rem;
}

.preview-status {
    font-size: 0.8rem;
    color: var(--color-text-light);
}

.preview-status.uploading {
    color: var(--color-primary);
}

.preview-status.success {
    color: var(--color-success);
}

.preview-status.error {
    color: var(--color-error);
}

.upload-error {
    background: #ffebee;
    color: #c62828;
    padding: 0.75rem;
    border-radius: 8px;
    margin-top: 1rem;
    font-size: 0.9rem;
    display: none;
}

@media (max-width: 768px) {
    .upload-previews {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .upload-zone {
        padding: 1.5rem;
    }
    
    .upload-text h3 {
        font-size: 1.1rem;
    }
}
</style>
`;

// Injecter les styles
if (!document.querySelector('#upload-styles')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'upload-styles';
    styleElement.textContent = uploadStyles.replace(/<\/?style>/g, ''); // Remove style tags
    document.head.appendChild(styleElement);
}

// Export pour les modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageUploadManager;
}

// Variable globale
window.ImageUploadManager = ImageUploadManager;