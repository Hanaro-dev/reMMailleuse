/**
 * Module Formulaires - Remmailleuse
 * Gestion des formulaires de contact et calculateur de prix
 */

class FormsManager {
    constructor(app) {
        this.app = app;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFileUpload();
    }

    setupEventListeners() {
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
                this.app.showToast(`❌ ${file.name} est trop volumineux (max 5MB)`, 'error');
                return false;
            }
            if (!file.type.startsWith('image/')) {
                this.app.showToast(`❌ ${file.name} n'est pas une image`, 'error');
                return false;
            }
            return true;
        });

        if (!filePreview) return;
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
            this.app.showToast(`📷 ${validFiles.length} image(s) ajoutée(s)`, 'success');
        }
    }

    handlePriceCalculator(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const garmentType = formData.get('garment-type') || document.getElementById('garment-type')?.value;
        const damageType = formData.get('damage-type') || document.getElementById('damage-type')?.value;
        const damageSize = formData.get('damage-size') || document.getElementById('damage-size')?.value;

        if (!garmentType || !damageType) {
            this.app.showToast('⚠️ Veuillez remplir tous les champs obligatoires', 'warning');
            return;
        }

        // Logique de calcul basique
        const estimate = this.calculatePrice(garmentType, damageType, damageSize);
        this.displayPriceEstimate(estimate);
        
        this.app.showToast('💰 Estimation calculée !', 'success');
    }

    calculatePrice(garmentType, damageType, damageSize) {
        let basePrice = 20;
        
        const garmentMultipliers = {
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

        basePrice *= (garmentMultipliers[garmentType] || 1.0);
        basePrice *= (damageMultipliers[damageType] || 1.0);

        if (damageSize) {
            const size = parseFloat(damageSize);
            if (size > 5) basePrice *= 1.5;
            if (size > 10) basePrice *= 2;
        }

        const estimatedPrice = Math.round(basePrice);
        return {
            price: estimatedPrice,
            range: `${estimatedPrice - 5}-${estimatedPrice + 10}€`,
            garmentType,
            damageType,
            damageSize
        };
    }

    displayPriceEstimate(estimate) {
        const resultElement = document.getElementById('estimate-result');
        const priceElement = resultElement?.querySelector('.estimate-price');
        
        if (priceElement) {
            priceElement.textContent = `Estimation : ${estimate.range}`;
        }
        
        if (resultElement) {
            resultElement.style.display = 'block';
        }
    }

    async handleContactForm(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('contact-submit');
        const btnText = submitBtn?.querySelector('.btn-text');
        const btnLoading = submitBtn?.querySelector('.btn-loading');

        // UI Loading state
        if (submitBtn) submitBtn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'inline';

        try {
            const formData = new FormData(e.target);
            
            // Validation
            this.validateContactForm(formData);
            
            // Simulation d'envoi (à remplacer par l'API réelle)
            await this.submitContactForm(formData);
            
            // Succès
            this.app.showToast('✅ Votre demande a été envoyée avec succès !', 'success');
            e.target.reset();
            
            const filePreview = document.getElementById('file-preview');
            if (filePreview) filePreview.innerHTML = '';
            
        } catch (error) {
            // Erreur envoi formulaire
            this.app.showToast(`❌ Erreur : ${error.message}`, 'error');
        } finally {
            // Reset UI
            if (submitBtn) submitBtn.disabled = false;
            if (btnText) btnText.style.display = 'inline';
            if (btnLoading) btnLoading.style.display = 'none';
        }
    }

    validateContactForm(formData) {
        const required = ['firstname', 'lastname', 'email', 'message'];
        
        for (const field of required) {
            if (!formData.get(field)) {
                throw new Error(`Le champ ${this.getFieldLabel(field)} est obligatoire`);
            }
        }

        // Validation email
        const email = formData.get('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            throw new Error('L\'adresse email n\'est pas valide');
        }

        // Validation téléphone (optionnel mais format si présent)
        const phone = formData.get('phone');
        if (phone) {
            const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
            if (!phoneRegex.test(phone)) {
                throw new Error('Le numéro de téléphone n\'est pas valide');
            }
        }
    }

    getFieldLabel(fieldName) {
        const labels = {
            'firstname': 'Prénom',
            'lastname': 'Nom',
            'email': 'Email',
            'phone': 'Téléphone',
            'message': 'Message',
            'subject': 'Sujet'
        };
        return labels[fieldName] || fieldName;
    }

    async submitContactForm(formData) {
        // Simulation d'un délai d'envoi
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Log pour développement
        // Formulaire soumis
        
        // Dans un vrai projet, on enverrait à une API
        // const response = await fetch('/api/contact', {
        //     method: 'POST',
        //     body: formData
        // });
        // 
        // if (!response.ok) {
        //     throw new Error(`Erreur serveur: ${response.status}`);
        // }
        // 
        // return response.json();
        
        return { success: true, message: 'Formulaire envoyé avec succès' };
    }

    // Méthodes utilitaires publiques
    resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            const filePreview = form.querySelector('#file-preview');
            if (filePreview) filePreview.innerHTML = '';
        }
    }

    setFormData(formId, data) {
        const form = document.getElementById(formId);
        if (!form) return;

        Object.entries(data).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value;
            }
        });
    }

    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return null;

        const formData = new FormData(form);
        return Object.fromEntries(formData);
    }
}

// Export pour utilisation externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormsManager;
}