/**
 * Admin Remmailleuse - Interface d'administration
 * Gestion du contenu, des médias et des paramètres
 */

class AdminApp {
    constructor() {
        this.data = {};
        this.hasUnsavedChanges = false;
        this.currentSection = 'hero-section';
        this.autosaveInterval = null;
        this.init();
    }

    async init() {
        try {
            // Vérifier l'authentification d'abord
            const authCheck = await this.checkAuthentication();
            if (!authCheck) {
                window.location.href = './login.html';
                return;
            }
            
            this.showLoading();
            await this.loadData();
            this.setupEventListeners();
            this.populateFields();
            this.setupAutosave();
            this.setupAuthRefresh();
            this.hideLoading();
            this.showStatus('🎉 Interface d\'administration chargée !', 'success');
        } catch (error) {
            // Erreur d'initialisation admin
            this.hideLoading();
            this.showStatus('❌ Erreur de chargement de l\'administration', 'error');
        }
    }

    // ===== AUTHENTIFICATION =====
    async checkAuthentication() {
        try {
            const response = await fetch('../api/auth.php?action=check');
            const data = await response.json();
            return data.authenticated === true;
        } catch (error) {
            // Erreur de vérification d'authentification
            return false;
        }
    }

    setupAuthRefresh() {
        // Rafraîchir la session toutes les 30 minutes
        setInterval(async () => {
            try {
                const response = await fetch('../api/auth.php?action=refresh', {
                    method: 'POST'
                });
                if (!response.ok) {
                    window.location.href = './login.html';
                }
            } catch (error) {
                // Erreur de rafraîchissement de session
            }
        }, 30 * 60 * 1000);
    }

    // ===== CHARGEMENT DES DONNÉES =====
    async loadData() {
        try {
            const [contentResponse, servicesResponse, galleryResponse, settingsResponse] = await Promise.all([
                fetch('../data/content.json').catch(() => ({ ok: false })),
                fetch('../data/services.json').catch(() => ({ ok: false })),
                fetch('../data/gallery.json').catch(() => ({ ok: false })),
                fetch('../data/settings.json').catch(() => ({ ok: false }))
            ]);

            this.data = {
                content: contentResponse.ok ? await contentResponse.json() : this.getDefaultContent(),
                services: servicesResponse.ok ? await servicesResponse.json() : this.getDefaultServices(),
                gallery: galleryResponse.ok ? await galleryResponse.json() : this.getDefaultGallery(),
                settings: settingsResponse.ok ? await settingsResponse.json() : this.getDefaultSettings()
            };

            // Données chargées
        } catch (error) {
            // Erreur lors du chargement
            this.data = {
                content: this.getDefaultContent(),
                services: this.getDefaultServices(),
                gallery: this.getDefaultGallery(),
                settings: this.getDefaultSettings()
            };
        }
    }

    getDefaultContent() {
        return {
            hero: {
                title: "L'art de redonner vie à vos tissus précieux",
                subtitle: "Remaillage traditionnel & réparation invisible depuis plus de 20 ans",
                cta: {
                    text: "Découvrir mon savoir-faire",
                    link: "#expertise"
                }
            },
            expertise: {
                intro: {
                    name: "Mme Monod, Artisane Remmailleuse",
                    description: [
                        "Passionnée par les techniques traditionnelles de remaillage, je redonne vie à vos tissus et lainages les plus précieux. Mon travail consiste à réparer minutieusement chaque maille avec une loupe et un crochet minuscule, remontant maille par maille les lainages endommagés.",
                        "Que ce soit pour refermer un trou de mite avec la plus grande minutie ou effectuer du raccommodage avec ma fidèle machine Elna vintage, j'apporte le plus grand soin à rénover vos tissus à l'identique."
                    ]
                },
                process: [
                    { step: 1, icon: "🔍", title: "Diagnostic", description: "Analyse minutieuse de la pièce pour déterminer la meilleure technique de réparation" },
                    { step: 2, icon: "🧵", title: "Remaillage", description: "Reconstruction maille par maille avec loupe et outils traditionnels" },
                    { step: 3, icon: "✨", title: "Finition", description: "Réparation invisible qui redonne une seconde vie à votre vêtement" }
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
        };
    }

    getDefaultServices() {
        return {
            services: [
                { id: "remaillage", icon: "🧵", name: "Remaillage classique", description: "Reconstruction maille par maille pour lainages", price: "15-40€" },
                { id: "mite", icon: "🔍", name: "Trous de mite", description: "Réparation invisible minutieuse", price: "20-35€" },
                { id: "bas", icon: "🧦", name: "Bas de contention", description: "Raccommodage machine spécialisée", price: "15-25€" },
                { id: "renovation", icon: "✨", name: "Rénovation tissus", description: "Restauration à l'identique", price: "Sur devis" }
            ]
        };
    }

    getDefaultGallery() {
        return {
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
        };
    }

    getDefaultSettings() {
        return {
            site: {
                name: "Remmailleuse",
                description: "L'art traditionnel du remaillage pour redonner vie à vos tissus précieux",
                keywords: "remaillage, réparation, tissus, lainages, artisan"
            },
            theme: {
                colors: {
                    primary: "#D4896B",
                    secondary: "#9CAF9A",
                    accent: "#8B6F47",
                    neutral: "#F5F1EB"
                }
            },
            seo: {
                title: "Remmailleuse - Réparation de tissus et lainages",
                description: "Artisane spécialisée en remaillage traditionnel. Réparation invisible de pulls, bas de contention et tissus délicats en Suisse et France."
            }
        };
    }

    // ===== POPULATION DES CHAMPS =====
    populateFields() {
        this.populateHeroFields();
        this.populateExpertiseFields();
        this.populateContactFields();
        this.populateSettingsFields();
        this.renderProcessSteps();
        this.renderGalleryItems();
        this.renderServiceItems();
    }

    populateHeroFields() {
        const hero = this.data.content.hero;
        this.setFieldValue('hero-title', hero.title);
        this.setFieldValue('hero-subtitle', hero.subtitle);
        this.setFieldValue('hero-cta-text', hero.cta.text);
        this.setFieldValue('hero-cta-link', hero.cta.link);
    }

    populateExpertiseFields() {
        const expertise = this.data.content.expertise;
        this.setFieldValue('expert-name', expertise.intro.name);
        this.setFieldValue('expert-desc-1', expertise.intro.description[0] || '');
        this.setFieldValue('expert-desc-2', expertise.intro.description[1] || '');
    }

    populateContactFields() {
        const contact = this.data.content.contact;
        
        if (contact.addresses[0]) {
            this.setFieldValue('address-ch', contact.addresses[0].address);
            this.setFieldValue('city-ch', contact.addresses[0].city);
        }
        
        if (contact.addresses[1]) {
            this.setFieldValue('address-fr', contact.addresses[1].address);
            this.setFieldValue('city-fr', contact.addresses[1].city);
        }
        
        this.setFieldValue('phone-1', contact.phones[0] || '');
        this.setFieldValue('phone-2', contact.phones[1] || '');
        this.setFieldValue('contact-email', contact.email);
        this.setFieldValue('repair-delays', contact.delays);
    }

    populateSettingsFields() {
        const settings = this.data.settings;
        this.setFieldValue('site-name', settings.site?.name || '');
        this.setFieldValue('site-description', settings.seo?.description || '');
        this.setFieldValue('site-keywords', settings.seo?.keywords || '');
        
        if (settings.theme?.colors) {
            this.setFieldValue('color-primary', settings.theme.colors.primary);
            this.setFieldValue('color-secondary', settings.theme.colors.secondary);
            this.setFieldValue('color-accent', settings.theme.colors.accent);
            this.setFieldValue('color-neutral', settings.theme.colors.neutral);
        }
    }

    setFieldValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (field && value !== undefined) {
            field.value = value;
        }
    }

    // ===== RENDU DES ÉLÉMENTS DYNAMIQUES =====
    renderProcessSteps() {
        const container = document.getElementById('process-steps-admin');
        if (!container) return;

        const steps = this.data.content.expertise.process || [];
        
        container.innerHTML = steps.map((step, index) => `
            <div class="process-step-admin" data-step-index="${index}">
                <input type="text" class="step-icon-input" value="${step.icon}" placeholder="🔍" 
                       onchange="adminApp.updateProcessStep(${index}, 'icon', this.value)">
                <div class="step-details">
                    <input type="text" class="form-input mb-1" value="${step.title}" placeholder="Titre de l'étape"
                           onchange="adminApp.updateProcessStep(${index}, 'title', this.value)">
                    <textarea class="form-input" rows="2" placeholder="Description de l'étape"
                              onchange="adminApp.updateProcessStep(${index}, 'description', this.value)">${step.description}</textarea>
                </div>
                <div class="flex flex-wrap gap-1">
                    <div class="step-number">${step.step}</div>
                    <button class="btn btn-error btn-sm" onclick="adminApp.removeProcessStep(${index})">🗑️</button>
                </div>
            </div>
        `).join('');
    }

    renderGalleryItems() {
        const container = document.getElementById('gallery-admin');
        if (!container) return;

        const items = this.data.gallery.items || [];
        
        container.innerHTML = `
            <div class="gallery-admin">
                ${items.map((item, index) => `
                    <div class="gallery-item-admin" data-item-index="${index}">
                        <div class="gallery-preview">
                            ${item.images?.before ? 
                                `<img src="${item.images.before}" alt="${item.title}">` :
                                `<div style="font-size: 3rem;">${item.icon}</div>`
                            }
                            <input type="file" accept="image/*" onchange="adminApp.handleImageUpload(${index}, this)">
                        </div>
                        <div class="gallery-info">
                            <input type="text" class="form-input mb-1" value="${item.title}" placeholder="Nom de la réalisation"
                                   onchange="adminApp.updateGalleryItem(${index}, 'title', this.value)">
                            <textarea class="form-input mb-1" rows="2" placeholder="Description"
                                      onchange="adminApp.updateGalleryItem(${index}, 'description', this.value)">${item.description}</textarea>
                            <select class="form-input mb-1" onchange="adminApp.updateGalleryItem(${index}, 'category', this.value)">
                                ${this.data.gallery.categories.map(cat => 
                                    `<option value="${cat.id}" ${item.category === cat.id ? 'selected' : ''}>${cat.name}</option>`
                                ).join('')}
                            </select>
                            <div class="gallery-actions">
                                <button class="btn btn-outline btn-sm">📷 Photo avant</button>
                                <button class="btn btn-outline btn-sm">📷 Photo après</button>
                                <button class="btn btn-error btn-sm" onclick="adminApp.removeGalleryItem(${index})">🗑️</button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderServiceItems() {
        const container = document.getElementById('services-admin');
        if (!container) return;

        const services = this.data.services.services || [];
        
        container.innerHTML = `
            <div class="services-admin">
                ${services.map((service, index) => `
                    <div class="service-item-admin" data-service-index="${index}">
                        <input type="text" class="service-icon-input" value="${service.icon}" placeholder="🧵"
                               onchange="adminApp.updateService(${index}, 'icon', this.value)">
                        <div class="service-details">
                            <input type="text" class="form-input mb-1" value="${service.name}" placeholder="Nom du service"
                                   onchange="adminApp.updateService(${index}, 'name', this.value)">
                            <textarea class="form-input" rows="2" placeholder="Description"
                                      onchange="adminApp.updateService(${index}, 'description', this.value)">${service.description}</textarea>
                        </div>
                        <div>
                            <input type="text" class="form-input service-price-input text-center mb-2" value="${service.price}" placeholder="Prix"
                                   onchange="adminApp.updateService(${index}, 'price', this.value)">
                            <button class="btn btn-error btn-sm" onclick="adminApp.removeService(${index})">🗑️ Supprimer</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // ===== GESTION DES SECTIONS =====
    showSection(sectionId, linkElement) {
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
        
        // Activer le lien cliqué
        if (linkElement) {
            linkElement.classList.add('active');
        }
    }

    // ===== MISE À JOUR DES DONNÉES =====
    updateProcessStep(index, field, value) {
        if (!this.data.content.expertise.process[index]) return;
        
        this.data.content.expertise.process[index][field] = value;
        this.markAsChanged();
    }

    updateGalleryItem(index, field, value) {
        if (!this.data.gallery.items[index]) return;
        
        this.data.gallery.items[index][field] = value;
        this.markAsChanged();
    }

    updateService(index, field, value) {
        if (!this.data.services.services[index]) return;
        
        this.data.services.services[index][field] = value;
        this.markAsChanged();
    }

    addProcessStep() {
        const newStep = {
            step: this.data.content.expertise.process.length + 1,
            icon: "🆕",
            title: "Nouvelle étape",
            description: "Description de la nouvelle étape"
        };
        
        this.data.content.expertise.process.push(newStep);
        this.renderProcessSteps();
        this.markAsChanged();
        this.showStatus('➕ Nouvelle étape ajoutée !', 'success');
    }

    removeProcessStep(index) {
        this.confirmAction(
            'Supprimer l\'étape',
            'Êtes-vous sûr de vouloir supprimer cette étape ?',
            () => {
                this.data.content.expertise.process.splice(index, 1);
                // Réorganiser les numéros d'étapes
                this.data.content.expertise.process.forEach((step, i) => {
                    step.step = i + 1;
                });
                this.renderProcessSteps();
                this.markAsChanged();
                this.showStatus('🗑️ Étape supprimée', 'warning');
            }
        );
    }

    addGalleryItem() {
        const newItem = {
            id: `item-${Date.now()}`,
            category: "tous",
            title: "Nouvelle réalisation",
            description: "Description de la nouvelle réalisation",
            icon: "📷"
        };
        
        this.data.gallery.items.push(newItem);
        this.renderGalleryItems();
        this.markAsChanged();
        this.showStatus('➕ Nouvelle réalisation ajoutée !', 'success');
    }

    removeGalleryItem(index) {
        this.confirmAction(
            'Supprimer la réalisation',
            'Êtes-vous sûr de vouloir supprimer cette réalisation ?',
            () => {
                this.data.gallery.items.splice(index, 1);
                this.renderGalleryItems();
                this.markAsChanged();
                this.showStatus('🗑️ Réalisation supprimée', 'warning');
            }
        );
    }

    addService() {
        const newService = {
            id: `service-${Date.now()}`,
            icon: "🆕",
            name: "Nouveau service",
            description: "Description du nouveau service",
            price: "Sur devis"
        };
        
        this.data.services.services.push(newService);
        this.renderServiceItems();
        this.markAsChanged();
        this.showStatus('➕ Nouveau service ajouté !', 'success');
    }

    removeService(index) {
        this.confirmAction(
            'Supprimer le service',
            'Êtes-vous sûr de vouloir supprimer ce service ?',
            () => {
                this.data.services.services.splice(index, 1);
                this.renderServiceItems();
                this.markAsChanged();
                this.showStatus('🗑️ Service supprimé', 'warning');
            }
        );
    }

    // ===== GESTION DES IMAGES =====
    handleImageUpload(itemIndex, input) {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) { // 5MB max
            this.showStatus('❌ Image trop volumineuse (max 5MB)', 'error');
            return;
        }

        if (!file.type.startsWith('image/')) {
            this.showStatus('❌ Le fichier doit être une image', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            // Mettre à jour l'affichage
            const preview = input.parentElement.querySelector('img');
            if (preview) {
                preview.src = e.target.result;
            } else {
                input.parentElement.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            }
            
            // Sauvegarder en base64 (pour demo, en production on uploadrait sur le serveur)
            if (!this.data.gallery.items[itemIndex].images) {
                this.data.gallery.items[itemIndex].images = {};
            }
            this.data.gallery.items[itemIndex].images.before = e.target.result;
            
            this.markAsChanged();
            this.showStatus('📷 Image mise à jour !', 'success');
        };
        
        reader.readAsDataURL(file);
    }

    // ===== SAUVEGARDE =====
    collectFormData() {
        // Hero
        this.data.content.hero.title = this.getFieldValue('hero-title');
        this.data.content.hero.subtitle = this.getFieldValue('hero-subtitle');
        this.data.content.hero.cta.text = this.getFieldValue('hero-cta-text');
        this.data.content.hero.cta.link = this.getFieldValue('hero-cta-link');

        // Expertise
        this.data.content.expertise.intro.name = this.getFieldValue('expert-name');
        this.data.content.expertise.intro.description = [
            this.getFieldValue('expert-desc-1'),
            this.getFieldValue('expert-desc-2')
        ].filter(desc => desc.trim());

        // Contact
        this.data.content.contact.addresses[0] = {
            country: "🇨🇭",
            title: "Suisse",
            address: this.getFieldValue('address-ch'),
            city: this.getFieldValue('city-ch')
        };
        
        this.data.content.contact.addresses[1] = {
            country: "🇫🇷",
            title: "France", 
            address: this.getFieldValue('address-fr'),
            city: this.getFieldValue('city-fr')
        };
        
        this.data.content.contact.phones = [
            this.getFieldValue('phone-1'),
            this.getFieldValue('phone-2')
        ].filter(phone => phone.trim());
        
        this.data.content.contact.email = this.getFieldValue('contact-email');
        this.data.content.contact.delays = this.getFieldValue('repair-delays');

        // Settings
        this.data.settings.site.name = this.getFieldValue('site-name');
        this.data.settings.seo.description = this.getFieldValue('site-description');
        this.data.settings.seo.keywords = this.getFieldValue('site-keywords');
        
        this.data.settings.theme.colors = {
            primary: this.getFieldValue('color-primary'),
            secondary: this.getFieldValue('color-secondary'),
            accent: this.getFieldValue('color-accent'),
            neutral: this.getFieldValue('color-neutral')
        };
    }

    getFieldValue(fieldId) {
        const field = document.getElementById(fieldId);
        return field ? field.value : '';
    }

    async autoSave(section = null) {
        this.collectFormData();
        
        try {
            // En production, on enverrait au serveur
            // await this.saveToServer();
            
            // Pour la demo, on sauvegarde en localStorage
            localStorage.setItem('remmailleuse-admin-data', JSON.stringify(this.data));
            
            this.hasUnsavedChanges = false;
            const sectionName = section ? `section "${section}"` : 'modifications';
            this.showStatus(`✅ ${sectionName.charAt(0).toUpperCase() + sectionName.slice(1)} sauvegardée(s) !`, 'success');
            
        } catch (error) {
            // Erreur de sauvegarde
            this.showStatus('❌ Erreur lors de la sauvegarde', 'error');
        }
    }

    async saveAll() {
        this.showLoading('Sauvegarde en cours...');
        
        try {
            await this.autoSave();
            this.hideLoading();
            this.showStatus('💾 Toutes les modifications ont été sauvegardées !', 'success');
        } catch (error) {
            this.hideLoading();
            this.showStatus('❌ Erreur lors de la sauvegarde complète', 'error');
        }
    }

    // ===== FONCTIONS UTILITAIRES =====
    markAsChanged() {
        this.hasUnsavedChanges = true;
        
        // Indicateur visuel
        const currentSectionLink = document.querySelector('.sidebar-link.active');
        if (currentSectionLink && !currentSectionLink.textContent.includes('*')) {
            currentSectionLink.textContent += ' *';
        }
    }

    setupAutosave() {
        // Auto-save toutes les 30 secondes
        this.autosaveInterval = setInterval(() => {
            if (this.hasUnsavedChanges) {
                this.autoSave();
            }
        }, 30000);

        // Auto-save sur changement des inputs
        document.addEventListener('input', (e) => {
            if (e.target.matches('.form-input')) {
                this.markAsChanged();
            }
        });
    }

    setupEventListeners() {
        // Liens de navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = link.getAttribute('href').substring(1);
                this.showSection(sectionId, link);
            });
        });

        // Validation des formulaires
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[type="email"]')) {
                this.validateEmail(e.target);
            } else if (e.target.matches('input[type="tel"]')) {
                this.validatePhone(e.target);
            } else if (e.target.matches('input[type="color"]')) {
                this.previewColorChange(e.target);
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 's') {
                    e.preventDefault();
                    this.autoSave(this.currentSection);
                }
            }
        });
    }

    validateEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(input.value);
        
        input.style.borderColor = isValid ? 'var(--color-success)' : 'var(--color-error)';
        
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('form-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'form-feedback';
            input.parentNode.appendChild(feedback);
        }
        
        feedback.textContent = isValid ? '✅ Email valide' : '❌ Format d\'email invalide';
        feedback.className = `form-feedback ${isValid ? 'form-success' : 'form-error'}`;
    }

    validatePhone(input) {
        const phoneRegex = /^\+?[\d\s\.\-\(\)]{8,}$/;
        const isValid = phoneRegex.test(input.value);
        
        input.style.borderColor = isValid ? 'var(--color-success)' : 'var(--color-error)';
    }

    previewColorChange(input) {
        const colorName = input.id.replace('color-', '');
        document.documentElement.style.setProperty(`--color-${colorName}`, input.value);
    }

    applyThemePreview() {
        const colors = {
            primary: this.getFieldValue('color-primary'),
            secondary: this.getFieldValue('color-secondary'),
            accent: this.getFieldValue('color-accent'),
            neutral: this.getFieldValue('color-neutral')
        };

        Object.entries(colors).forEach(([name, value]) => {
            if (value) {
                document.documentElement.style.setProperty(`--color-${name}`, value);
            }
        });

        this.showStatus('🎨 Aperçu des couleurs appliqué !', 'info');
    }

    // ===== FONCTIONS GLOBALES =====
    previewSite() {
        // Sauvegarder avant la prévisualisation
        this.autoSave().then(() => {
            window.open('../index.html', '_blank');
        });
    }

    exportData() {
        this.collectFormData();
        
        const dataStr = JSON.stringify(this.data, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `remmailleuse-backup-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        
        URL.revokeObjectURL(url);
        this.showStatus('📤 Données exportées !', 'success');
    }

    importData(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const importedData = JSON.parse(e.target.result);
                this.data = { ...this.data, ...importedData };
                this.populateFields();
                this.renderProcessSteps();
                this.renderGalleryItems();
                this.renderServiceItems();
                this.markAsChanged();
                this.showStatus('📥 Données importées avec succès !', 'success');
            } catch (error) {
                this.showStatus('❌ Erreur lors de l\'import : fichier invalide', 'error');
            }
        };
        reader.readAsText(file);
    }

    // ===== UI HELPERS =====
    showLoading(message = 'Chargement...') {
        const modal = document.getElementById('loading-modal');
        if (modal) {
            modal.style.display = 'flex';
            const messageEl = modal.querySelector('p');
            if (messageEl) messageEl.textContent = message;
        }
    }

    hideLoading() {
        const modal = document.getElementById('loading-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showStatus(message, type = 'success') {
        const statusBar = document.getElementById('status-bar');
        if (!statusBar) return;

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        statusBar.innerHTML = `${icons[type]} ${message}`;
        statusBar.className = `status-bar show ${type}`;

        // Auto-hide après 4 secondes
        setTimeout(() => {
            statusBar.classList.remove('show');
        }, 4000);
    }

    confirmAction(title, message, callback) {
        const modal = document.getElementById('confirm-modal');
        const titleEl = document.getElementById('confirm-title');
        const messageEl = document.getElementById('confirm-message');
        const confirmBtn = document.getElementById('confirm-action');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;
        
        if (modal) modal.style.display = 'flex';

        confirmBtn.onclick = () => {
            callback();
            this.closeConfirmModal();
        };
    }

    closeConfirmModal() {
        const modal = document.getElementById('confirm-modal');
        if (modal) modal.style.display = 'none';
    }
}

// ===== FONCTIONS GLOBALES POUR L'HTML =====
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

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
    window.adminApp = new AdminApp();
    
    // Interface d'administration initialisée
    
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
    // Erreur JavaScript Admin
    if (window.adminApp) {
        window.adminApp.showStatus('❌ Une erreur est survenue', 'error');
    }
});

window.addEventListener('unhandledrejection', (e) => {
    // Promise rejetée Admin
    if (window.adminApp) {
        window.adminApp.showStatus('❌ Erreur de traitement', 'error');
    }
});

// ===== SAUVEGARDE AVANT FERMETURE =====
window.addEventListener('beforeunload', (e) => {
    if (window.adminApp && window.adminApp.hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
        return e.returnValue;
    }
});

// ===== UTILITAIRES SUPPLÉMENTAIRES =====

/**
 * Validation en temps réel des formulaires
 */
class FormValidator {
    static validateRequired(input) {
        const isValid = input.value.trim() !== '';
        FormValidator.setValidationState(input, isValid, isValid ? '' : 'Ce champ est obligatoire');
        return isValid;
    }
    
    static validateEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(input.value);
        FormValidator.setValidationState(input, isValid, isValid ? '' : 'Format d\'email invalide');
        return isValid;
    }
    
    static validatePhone(input) {
        const phoneRegex = /^\+?[\d\s\.\-\(\)]{8,}$/;
        const isValid = phoneRegex.test(input.value);
        FormValidator.setValidationState(input, isValid, isValid ? '' : 'Format de téléphone invalide');
        return isValid;
    }
    
    static validateUrl(input) {
        try {
            new URL(input.value);
            FormValidator.setValidationState(input, true, '');
            return true;
        } catch {
            FormValidator.setValidationState(input, false, 'URL invalide');
            return false;
        }
    }
    
    static setValidationState(input, isValid, message) {
        // Couleur de bordure
        input.style.borderColor = isValid ? 'var(--color-success)' : 'var(--color-error)';
        
        // Message de feedback
        let feedback = input.parentNode.querySelector('.form-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'form-feedback';
            input.parentNode.appendChild(feedback);
        }
        
        feedback.textContent = message;
        feedback.className = `form-feedback ${isValid ? 'form-success' : 'form-error'}`;
        feedback.style.display = message ? 'block' : 'none';
    }
}

/**
 * Gestionnaire d'images avec preview et compression
 */
class ImageHandler {
    static async handleImageUpload(file, maxSize = 5 * 1024 * 1024, quality = 0.8) {
        return new Promise((resolve, reject) => {
            if (file.size > maxSize) {
                reject(new Error(`Image trop volumineuse (max ${Math.round(maxSize / 1024 / 1024)}MB)`));
                return;
            }
            
            if (!file.type.startsWith('image/')) {
                reject(new Error('Le fichier doit être une image'));
                return;
            }
            
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                // Redimensionnement si nécessaire
                const maxWidth = 1200;
                const maxHeight = 800;
                
                let { width, height } = img;
                
                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
                
                if (height > maxHeight) {
                    width = (width * maxHeight) / height;
                    height = maxHeight;
                }
                
                canvas.width = width;
                canvas.height = height;
                
                // Dessin et compression
                ctx.drawImage(img, 0, 0, width, height);
                const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                
                resolve({
                    dataUrl: compressedDataUrl,
                    originalSize: file.size,
                    compressedSize: Math.round(compressedDataUrl.length * 0.75), // Approximation
                    dimensions: { width, height }
                });
            };
            
            img.onerror = () => reject(new Error('Erreur lors du chargement de l\'image'));
            img.src = URL.createObjectURL(file);
        });
    }
    
    static createImagePreview(dataUrl, container, options = {}) {
        const { 
            removable = true, 
            className = 'image-preview',
            onRemove = null 
        } = options;
        
        const preview = document.createElement('div');
        preview.className = className;
        
        const img = document.createElement('img');
        img.src = dataUrl;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        
        preview.appendChild(img);
        
        if (removable) {
            const removeBtn = document.createElement('button');
            removeBtn.className = 'image-remove-btn';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = () => {
                preview.remove();
                if (onRemove) onRemove();
            };
            preview.appendChild(removeBtn);
        }
        
        container.appendChild(preview);
        return preview;
    }
}

/**
 * Gestionnaire de thèmes et couleurs
 */
class ThemeManager {
    static applyColors(colors) {
        Object.entries(colors).forEach(([name, value]) => {
            if (value && this.isValidColor(value)) {
                document.documentElement.style.setProperty(`--color-${name}`, value);
            }
        });
    }
    
    static isValidColor(color) {
        const s = new Option().style;
        s.color = color;
        return s.color !== '';
    }
    
    static generateColorPalette(baseColor) {
        // Génère une palette de couleurs basée sur une couleur de base
        const hsl = this.hexToHsl(baseColor);
        
        return {
            light: this.hslToHex(hsl.h, Math.max(0, hsl.s - 20), Math.min(100, hsl.l + 20)),
            dark: this.hslToHex(hsl.h, Math.min(100, hsl.s + 10), Math.max(0, hsl.l - 20)),
            muted: this.hslToHex(hsl.h, Math.max(0, hsl.s - 30), hsl.l),
            contrast: this.hslToHex((hsl.h + 180) % 360, hsl.s, hsl.l)
        };
    }
    
    static hexToHsl(hex) {
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;
        
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;
        
        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }
        
        return { h: h * 360, s: s * 100, l: l * 100 };
    }
    
    static hslToHex(h, s, l) {
        l /= 100;
        const a = s * Math.min(l, 1 - l) / 100;
        const f = n => {
            const k = (n + h / 30) % 12;
            const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
            return Math.round(255 * color).toString(16).padStart(2, '0');
        };
        return `#${f(0)}${f(8)}${f(4)}`;
    }
}

/**
 * Gestionnaire de données locales avec backup
 */
class DataManager {
    static save(key, data) {
        try {
            const timestamp = new Date().toISOString();
            const dataWithMeta = {
                data,
                timestamp,
                version: '1.0'
            };
            
            localStorage.setItem(key, JSON.stringify(dataWithMeta));
            
            // Backup automatique
            this.createBackup(key, dataWithMeta);
            
            return true;
        } catch (error) {
            // Erreur de sauvegarde
            return false;
        }
    }
    
    static load(key) {
        try {
            const stored = localStorage.getItem(key);
            if (!stored) return null;
            
            const parsed = JSON.parse(stored);
            return parsed.data || parsed; // Compatibilité avec anciens formats
        } catch (error) {
            // Erreur de chargement
            return null;
        }
    }
    
    static createBackup(key, data) {
        const backupKey = `${key}_backup_${Date.now()}`;
        try {
            localStorage.setItem(backupKey, JSON.stringify(data));
            
            // Garder seulement les 5 dernières sauvegardes
            this.cleanupBackups(key);
        } catch (error) {
            // Impossible de créer une sauvegarde
        }
    }
    
    static cleanupBackups(key) {
        const backupKeys = Object.keys(localStorage)
            .filter(k => k.startsWith(`${key}_backup_`))
            .sort()
            .reverse();
        
        // Supprimer les anciennes sauvegardes (garder les 5 plus récentes)
        backupKeys.slice(5).forEach(oldKey => {
            localStorage.removeItem(oldKey);
        });
    }
    
    static getBackups(key) {
        return Object.keys(localStorage)
            .filter(k => k.startsWith(`${key}_backup_`))
            .map(k => ({
                key: k,
                timestamp: new Date(parseInt(k.split('_').pop())),
                data: JSON.parse(localStorage.getItem(k))
            }))
            .sort((a, b) => b.timestamp - a.timestamp);
    }
}

// Exposition globale des utilitaires
window.FormValidator = FormValidator;
window.ImageHandler = ImageHandler;
window.ThemeManager = ThemeManager;
window.DataManager = DataManager;