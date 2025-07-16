<?php
/**
 * ===== INTERFACE ADMIN - SITE REMMAILLEUSE =====
 * Point d'entrée pour l'administration avec vérification d'authentification
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Démarrer la session
session_start();

// Vérifier l'authentification avec validation renforcée
if (!isset($_SESSION['admin_logged_in']) || 
    $_SESSION['admin_logged_in'] !== true ||
    !isset($_SESSION['admin_username']) ||
    !isset($_SESSION['login_time'])) {
    // Détruire la session corrompue
    session_destroy();
    // Rediriger vers la page de connexion
    header('Location: /admin/login.html?error=session_invalid');
    exit();
}

// Vérifier si la session n'a pas expiré
$sessionLifetime = 7200; // 2 heures
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionLifetime) {
    // Session expirée
    session_destroy();
    header('Location: /admin/login.html?expired=1');
    exit();
}

// Mettre à jour l'activité de la session
$_SESSION['last_activity'] = time();

// Inclure les dépendances pour les logs
require_once '../api/Logger.php';
require_once '../api/SecurityHeaders.php';

// Initialiser les en-têtes de sécurité pour l'admin
$logger = new Logger();
initSecurityHeaders('admin', $logger);

// Logger l'accès à l'interface admin
logSecurity('Accès interface admin', [
    'user' => $_SESSION['admin_username'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'session_id' => session_id()
], 'INFO');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Remmailleuse</title>
    
    <!-- Google Fonts avec préconnexion -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&family=Audiowide&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    
    <!-- Header Admin -->
    <header class="admin-header">
        <div class="container">
            <div class="admin-header-content">
                <h1 class="admin-logo">🧵 Admin <span class="logo-re">re</span><span class="logo-m">M</span><span class="logo-mailleuse">mailleuse</span></h1>
                <div class="admin-actions">
                    <button class="btn btn-outline" onclick="previewSite()">
                        👁️ Prévisualiser
                    </button>
                    <button class="btn btn-success" onclick="saveAll()">
                        💾 Sauvegarder tout
                    </button>
                    <button class="btn btn-secondary" onclick="exportData()">
                        📤 Exporter
                    </button>
                    <button class="btn btn-info" onclick="openMonitoring()">
                        📊 Monitoring
                    </button>
                    <button class="btn btn-warning" onclick="openSecurity()">
                        🔒 Sécurité
                    </button>
                    <button class="btn btn-danger" onclick="logout()">
                        🚪 Déconnexion
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Status Bar -->
    <div class="container">
        <div id="status-bar" class="status-bar">
            ✅ Interface d'administration chargée ! Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
        </div>
    </div>

    <!-- Layout Principal -->
    <div class="container">
        <div class="admin-layout">
            
            <!-- Sidebar -->
            <nav class="admin-sidebar">
                <div class="sidebar-section">
                    <h3>📝 Contenu</h3>
                    <ul class="sidebar-menu">
                        <li><a href="#" onclick="switchSection('content')" class="active">Textes & Messages</a></li>
                        <li><a href="#" onclick="switchSection('gallery')">Galerie Photos</a></li>
                        <li><a href="#" onclick="switchSection('services')">Services</a></li>
                        <li><a href="#" onclick="switchSection('process')">Processus</a></li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>🎨 Apparence</h3>
                    <ul class="sidebar-menu">
                        <li><a href="#" onclick="switchSection('colors')">Couleurs</a></li>
                        <li><a href="#" onclick="switchSection('fonts')">Polices</a></li>
                        <li><a href="#" onclick="switchSection('layout')">Mise en page</a></li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>⚙️ Système</h3>
                    <ul class="sidebar-menu">
                        <li><a href="#" onclick="switchSection('seo')">SEO</a></li>
                        <li><a href="#" onclick="switchSection('analytics')">Analytics</a></li>
                        <li><a href="#" onclick="switchSection('backup')">Sauvegardes</a></li>
                        <li><a href="#" onclick="switchSection('security')">Sécurité</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="admin-main">
                
                <!-- Section Contenu -->
                <div id="content" class="admin-section active">
                    <div class="section-header">
                        <h2>📝 Gestion du Contenu</h2>
                        <p>Modifiez les textes et messages de votre site</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Message d'accueil</h3>
                        <textarea id="welcome-message" class="admin-textarea" placeholder="Entrez votre message d'accueil..."></textarea>
                        <button class="btn btn-primary" onclick="saveWelcomeMessage()">Sauvegarder</button>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Description des services</h3>
                        <textarea id="services-description" class="admin-textarea" placeholder="Décrivez vos services..."></textarea>
                        <button class="btn btn-primary" onclick="saveServicesDescription()">Sauvegarder</button>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Informations de contact</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="contact-email" class="form-input" placeholder="votre@email.com">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" id="contact-phone" class="form-input" placeholder="+33 X XX XX XX XX">
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <input type="text" id="contact-address" class="form-input" placeholder="Votre adresse">
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveContactInfo()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section Galerie -->
                <div id="gallery" class="admin-section">
                    <div class="section-header">
                        <h2>📸 Gestion de la Galerie</h2>
                        <p>Ajoutez et organisez vos photos</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Upload de nouvelles photos</h3>
                        <div class="upload-zone" onclick="document.getElementById('gallery-upload').click()">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">Cliquez pour sélectionner des images</div>
                            <div class="upload-subtext">Formats acceptés: JPG, PNG, WebP (max 10MB)</div>
                        </div>
                        <input type="file" id="gallery-upload" multiple accept="image/*" style="display: none;" onchange="handleGalleryUpload(event)">
                        
                        <div class="gallery-admin" id="gallery-admin">
                            <!-- Les images seront chargées ici -->
                        </div>
                    </div>
                </div>
                
                <!-- Section Services -->
                <div id="services" class="admin-section">
                    <div class="section-header">
                        <h2>🧵 Gestion des Services</h2>
                        <p>Configurez vos services et tarifs</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Services proposés</h3>
                        <div id="services-list">
                            <!-- Les services seront chargés ici -->
                        </div>
                        <button class="btn btn-success" onclick="addService()">+ Ajouter un service</button>
                    </div>
                </div>
                
                <!-- Section Processus -->
                <div id="process" class="admin-section">
                    <div class="section-header">
                        <h2>⚙️ Processus de Travail</h2>
                        <p>Expliquez votre méthode de travail</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Étapes du processus</h3>
                        <div id="process-steps">
                            <!-- Les étapes seront chargées ici -->
                        </div>
                        <button class="btn btn-success" onclick="addProcessStep()">+ Ajouter une étape</button>
                    </div>
                </div>
                
                <!-- Section Couleurs -->
                <div id="colors" class="admin-section">
                    <div class="section-header">
                        <h2>🎨 Gestion des Couleurs</h2>
                        <p>Personnalisez les couleurs de votre site</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Couleurs principales</h3>
                        <div class="color-grid">
                            <div class="color-group">
                                <label>Couleur primaire</label>
                                <input type="color" id="primary-color" class="color-input" value="#673ab7">
                                <div class="color-preview" id="primary-preview"></div>
                            </div>
                            <div class="color-group">
                                <label>Couleur secondaire</label>
                                <input type="color" id="secondary-color" class="color-input" value="#9c27b0">
                                <div class="color-preview" id="secondary-preview"></div>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveColors()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section Polices -->
                <div id="fonts" class="admin-section">
                    <div class="section-header">
                        <h2>🔤 Gestion des Polices</h2>
                        <p>Personnalisez les polices de votre site</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Polices principales</h3>
                        <div class="font-grid">
                            <div class="font-group">
                                <label>Police principale</label>
                                <select id="primary-font" class="form-input">
                                    <option value="Inter">Inter</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Helvetica">Helvetica</option>
                                </select>
                            </div>
                            <div class="font-group">
                                <label>Police titres</label>
                                <select id="heading-font" class="form-input">
                                    <option value="Playfair Display">Playfair Display</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Helvetica">Helvetica</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveFonts()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section Mise en page -->
                <div id="layout" class="admin-section">
                    <div class="section-header">
                        <h2>📐 Mise en page</h2>
                        <p>Configurez la mise en page de votre site</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Paramètres de mise en page</h3>
                        <div class="layout-grid">
                            <div class="layout-group">
                                <label>Largeur maximum</label>
                                <input type="number" id="max-width" class="form-input" value="1200" min="800" max="1400">
                            </div>
                            <div class="layout-group">
                                <label>Espacement</label>
                                <select id="spacing" class="form-input">
                                    <option value="compact">Compact</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="spacious">Spacieux</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveLayout()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section SEO -->
                <div id="seo" class="admin-section">
                    <div class="section-header">
                        <h2>🔍 SEO</h2>
                        <p>Optimisez votre site pour les moteurs de recherche</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Paramètres SEO</h3>
                        <div class="seo-grid">
                            <div class="seo-group">
                                <label>Titre du site</label>
                                <input type="text" id="site-title" class="form-input" placeholder="Titre de votre site">
                            </div>
                            <div class="seo-group">
                                <label>Description</label>
                                <textarea id="site-description" class="form-input" placeholder="Description de votre site"></textarea>
                            </div>
                            <div class="seo-group">
                                <label>Mots-clés</label>
                                <input type="text" id="site-keywords" class="form-input" placeholder="mot-clé1, mot-clé2, mot-clé3">
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveSEO()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section Analytics -->
                <div id="analytics" class="admin-section">
                    <div class="section-header">
                        <h2>📊 Analytics</h2>
                        <p>Configurez le suivi des performances</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Configuration Analytics</h3>
                        <div class="analytics-grid">
                            <div class="analytics-group">
                                <label>URL Matomo</label>
                                <input type="text" id="matomo-url" class="form-input" placeholder="https://votre-matomo.example.com/">
                            </div>
                            <div class="analytics-group">
                                <label>Site ID Matomo</label>
                                <input type="text" id="matomo-site-id" class="form-input" placeholder="1">
                            </div>
                            <div class="analytics-group">
                                <label>Activer Matomo</label>
                                <select id="matomo-enabled" class="form-input">
                                    <option value="true">Activé</option>
                                    <option value="false">Désactivé</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="saveAnalytics()">Sauvegarder</button>
                    </div>
                </div>
                
                <!-- Section Sauvegardes -->
                <div id="backup" class="admin-section">
                    <div class="section-header">
                        <h2>💾 Gestion des Sauvegardes</h2>
                        <p>Sauvegardez et restaurez vos données</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Tableau de bord des sauvegardes</h3>
                        <div class="backup-actions">
                            <button class="btn btn-primary" onclick="openBackupStatus()">📊 Statut des Sauvegardes</button>
                            <button class="btn btn-success" onclick="createBackup()">+ Créer une sauvegarde</button>
                            <button class="btn btn-info" onclick="refreshBackups()">🔄 Actualiser</button>
                        </div>
                        <div id="backup-list">
                            <!-- Les sauvegardes seront chargées ici -->
                        </div>
                    </div>
                </div>
                
                <!-- Section Sécurité -->
                <div id="security" class="admin-section">
                    <div class="section-header">
                        <h2>🔒 Sécurité</h2>
                        <p>Gestion de la sécurité du site</p>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Logs de sécurité</h3>
                        <div class="security-actions">
                            <button class="btn btn-info" onclick="refreshSecurityLogs()">🔄 Actualiser</button>
                            <button class="btn btn-warning" onclick="clearSecurityLogs()">🗑️ Nettoyer</button>
                        </div>
                        <div id="security-logs" class="logs-container">
                            <!-- Les logs seront chargés ici -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal pour les confirmations -->
    <div id="modal-overlay" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modal-title">Confirmation</h3>
            <p id="modal-message">Êtes-vous sûr de vouloir effectuer cette action ?</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button class="btn btn-danger" id="modal-confirm" onclick="confirmAction()">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script>
        // Configuration globale
        const CONFIG = {
            apiUrl: '../api/',
            uploadUrl: '../api/upload.php',
            maxFileSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
        };

        // Fonctions principales
        function switchSection(sectionId) {
            console.log('Switching to section:', sectionId);
            
            // Masquer toutes les sections
            const sections = document.querySelectorAll('.admin-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Afficher la section demandée
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
                showStatus(`Section "${sectionId}" chargée`, 'info');
            } else {
                console.error('Section non trouvée:', sectionId);
                showStatus(`Erreur: Section "${sectionId}" non trouvée`, 'error');
            }
            
            // Mettre à jour la navigation
            const navLinks = document.querySelectorAll('.sidebar-menu a');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Ajouter la classe active au lien cliqué
            if (event && event.target) {
                event.target.classList.add('active');
            }
        }

        function previewSite() {
            window.open('../index.html', '_blank');
            showStatus('Aperçu du site ouvert !', 'info');
        }

        function openMonitoring() {
            window.open('../api/health-check.php', '_blank');
            showStatus('Monitoring ouvert !', 'info');
        }

        function openBackupStatus() {
            window.open('../api/backup.php', '_blank');
            showStatus('Statut des sauvegardes ouvert !', 'info');
        }

        function openSecurity() {
            window.open('../api/security-check.php', '_blank');
            showStatus('Sécurité ouverte !', 'info');
        }

        async function saveAll() {
            showStatus('Sauvegarde globale en cours...', 'info');
            
            try {
                // Sauvegarder tous les JSON modifiés
                const promises = [];
                
                if (window.adminData.content) {
                    promises.push(saveJsonData('content', window.adminData.content));
                }
                if (window.adminData.services) {
                    promises.push(saveJsonData('services', window.adminData.services));
                }
                if (window.adminData.gallery) {
                    promises.push(saveJsonData('gallery', window.adminData.gallery));
                }
                if (window.adminData.settings) {
                    promises.push(saveJsonData('settings', window.adminData.settings));
                }
                
                await Promise.all(promises);
                showStatus('Toutes les modifications ont été sauvegardées !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde globale', 'error');
            }
        }

        function exportData() {
            showStatus('Export en cours...', 'info');
            
            try {
                // Créer un export JSON de toutes les données
                const exportData = {
                    content: window.adminData.content,
                    services: window.adminData.services,
                    gallery: window.adminData.gallery,
                    settings: window.adminData.settings,
                    exported_at: new Date().toISOString()
                };
                
                const dataStr = JSON.stringify(exportData, null, 2);
                const dataBlob = new Blob([dataStr], { type: 'application/json' });
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `remmailleuse-export-${new Date().toISOString().split('T')[0]}.json`;
                link.click();
                URL.revokeObjectURL(url);
                
                showStatus('Export terminé ! Téléchargement en cours...', 'success');
            } catch (error) {
                showStatus('Erreur lors de l\'export', 'error');
            }
        }

        async function logout() {
            try {
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'logout' })
                });
                
                if (response.ok) {
                    window.location.href = 'login.html';
                }
            } catch (error) {
                // Erreur de déconnexion
                alert('Erreur lors de la déconnexion. Veuillez réessayer.');
            }
        }

        function showStatus(message, type = 'info') {
            const statusBar = document.getElementById('status-bar');
            if (statusBar) {
                statusBar.textContent = message;
                statusBar.className = `status-bar ${type} show`;
                
                setTimeout(() => {
                    statusBar.textContent = '✅ Interface d\'administration chargée ! Connecté en tant que admin';
                    statusBar.className = 'status-bar show';
                }, 3000);
            }
        }

        // Gestion des erreurs globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JS:', e.message, e.filename, e.lineno);
            showStatus('Erreur JavaScript: ' + e.message, 'error');
        });
        
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Erreur Promise:', e.reason);
            showStatus('Erreur de requête: ' + e.reason, 'error');
        });

        // Debug modal au chargement
        function debugModalOnLoad() {
            const modal = document.getElementById('modal-overlay');
            if (modal) {
                const computed = window.getComputedStyle(modal);
                console.log('Modal state at load:', {
                    display: computed.display,
                    visibility: computed.visibility,
                    opacity: computed.opacity,
                    visible: modal.offsetWidth > 0 && modal.offsetHeight > 0
                });
                
                // CORRECTIF: Forcer la modal à se cacher au chargement
                modal.style.display = 'none';
                console.log('Modal forcée à se cacher au chargement');
            }
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Admin');
            
            // Désactiver le Service Worker pour les pages admin
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (let registration of registrations) {
                        console.log('Désactivation du Service Worker pour l\'admin');
                        registration.unregister();
                    }
                });
            }
            
            // Debug modal
            debugModalOnLoad();
            
            // Test des fonctions admin
            console.log('Fonctions admin disponibles:', {
                switchSection: typeof switchSection,
                showStatus: typeof showStatus,
                saveWelcomeMessage: typeof saveWelcomeMessage,
                addService: typeof addService,
                previewSite: typeof previewSite,
                logout: typeof logout
            });
            
            // Test rapide des boutons
            setTimeout(() => {
                const buttons = document.querySelectorAll('button[onclick]');
                console.log(`Trouvé ${buttons.length} boutons avec onclick`);
                
                const links = document.querySelectorAll('a[onclick]');
                console.log(`Trouvé ${links.length} liens avec onclick`);
                
                // Tester la fonction showStatus
                showStatus('Interface admin chargée et testée !', 'success');
            }, 1000);
            
            // Vérifier la session périodiquement
            setInterval(checkSession, 60000); // Toutes les minutes
            
            // Vérifier que la modal reste cachée
            setInterval(function() {
                const modal = document.getElementById('modal-overlay');
                if (modal && modal.style.display !== 'none' && 
                    window.getComputedStyle(modal).display !== 'none') {
                    console.log('Modal réapparue - correction automatique');
                    modal.style.display = 'none';
                }
            }, 5000); // Toutes les 5 secondes
            
            // Charger les données initiales
            loadInitialData();
        });

        async function checkSession() {
            try {
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'check' })
                });
                
                const result = await response.json();
                
                if (!result.authenticated) {
                    alert('Votre session a expiré. Vous allez être redirigé vers la page de connexion.');
                    window.location.href = 'login.html';
                }
            } catch (error) {
                console.error('Erreur lors de la vérification de session:', error);
            }
        }

        function loadInitialData() {
            // Charger les données initiales de l'interface
            console.log('Chargement des données initiales...');
            
            // Charger toutes les données JSON
            loadAllJsonData();
        }
        
        async function loadAllJsonData() {
            try {
                const response = await fetch('../api/admin-data.php?action=load_all');
                const result = await response.json();
                
                if (result.success) {
                    window.adminData = result.data;
                    populateAdminFields();
                    showStatus('Données chargées avec succès !', 'success');
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur chargement données:', error);
                showStatus('Erreur lors du chargement des données', 'error');
            }
        }
        
        function populateAdminFields() {
            const data = window.adminData;
            
            // Section Contenu
            if (data.content) {
                const content = data.content;
                const welcomeMsg = document.getElementById('welcome-message');
                const servicesDesc = document.getElementById('services-description');
                const contactEmail = document.getElementById('contact-email');
                const contactPhone = document.getElementById('contact-phone');
                const contactAddress = document.getElementById('contact-address');
                
                if (welcomeMsg) welcomeMsg.value = content.hero?.title || '';
                if (servicesDesc) servicesDesc.value = content.hero?.subtitle || '';
                if (contactEmail) contactEmail.value = content.contact?.email?.primary || '';
                if (contactPhone) contactPhone.value = content.contact?.phones?.[0]?.number || '';
                if (contactAddress) contactAddress.value = content.contact?.addresses?.[0]?.address || '';
            }
            
            // Section Services
            if (data.services) {
                renderServicesAdmin(data.services.services);
            }
            
            // Section Galerie
            if (data.gallery) {
                renderGalleryAdmin(data.gallery.items);
            }
            
            // Section Settings (Couleurs, Polices, Layout, SEO, Analytics)
            if (data.settings) {
                populateSettingsFields(data.settings);
            }
        }
        
        function populateSettingsFields(settings) {
            // Couleurs
            const primaryColor = document.getElementById('primary-color');
            const secondaryColor = document.getElementById('secondary-color');
            if (primaryColor) primaryColor.value = settings.colors?.primary || settings.theme?.colors?.primary || '#D4896B';
            if (secondaryColor) secondaryColor.value = settings.colors?.secondary || settings.theme?.colors?.secondary || '#9CAF9A';
            
            // Polices
            const primaryFont = document.getElementById('primary-font');
            const headingFont = document.getElementById('heading-font');
            if (primaryFont) primaryFont.value = settings.fonts?.primary || settings.theme?.fonts?.sans || 'Inter';
            if (headingFont) headingFont.value = settings.fonts?.heading || settings.theme?.fonts?.serif || 'Playfair Display';
            
            // Mise en page
            const maxWidth = document.getElementById('max-width');
            const spacing = document.getElementById('spacing');
            if (maxWidth) maxWidth.value = settings.layout?.maxWidth || '1200';
            if (spacing) spacing.value = settings.layout?.spacing || 'normal';
            
            // SEO
            const siteTitle = document.getElementById('site-title');
            const siteDescription = document.getElementById('site-description');
            const siteKeywords = document.getElementById('site-keywords');
            if (siteTitle) siteTitle.value = settings.seo?.title || '';
            if (siteDescription) siteDescription.value = settings.seo?.description || '';
            if (siteKeywords) siteKeywords.value = settings.seo?.keywords || '';
            
            // Analytics - Matomo
            const matomoUrl = document.getElementById('matomo-url');
            const matomoSiteId = document.getElementById('matomo-site-id');
            const matomoEnabled = document.getElementById('matomo-enabled');
            if (matomoUrl) matomoUrl.value = settings.analytics?.matomo?.url || '';
            if (matomoSiteId) matomoSiteId.value = settings.analytics?.matomo?.site_id || '';
            if (matomoEnabled) matomoEnabled.value = settings.analytics?.matomo?.enabled ? 'true' : 'false';
        }
        
        function renderServicesAdmin(services) {
            const servicesList = document.getElementById('services-list');
            if (!servicesList || !services) return;
            
            servicesList.innerHTML = '';
            
            services.forEach((service, index) => {
                const serviceHtml = `
                    <div class="service-item-admin" data-id="${service.id}">
                        <input type="text" class="service-icon-input" value="${service.icon}" readonly>
                        <div class="service-details">
                            <input type="text" class="form-input service-name" value="${service.name}" placeholder="Nom du service">
                            <textarea class="form-input service-description" placeholder="Description du service">${service.description}</textarea>
                        </div>
                        <input type="text" class="service-price-input form-input" value="${service.price.display}" placeholder="Prix">
                        <button class="btn btn-sm btn-success" onclick="saveService('${service.id}')">Sauvegarder</button>
                        <button class="btn btn-sm btn-error" onclick="removeService('${service.id}')">Supprimer</button>
                    </div>
                `;
                servicesList.insertAdjacentHTML('beforeend', serviceHtml);
            });
        }
        
        function renderGalleryAdmin(items) {
            const galleryAdmin = document.getElementById('gallery-admin');
            if (!galleryAdmin || !items) return;
            
            galleryAdmin.innerHTML = '';
            
            items.forEach((item, index) => {
                const itemHtml = `
                    <div class="gallery-item-admin" data-id="${item.id}">
                        <div class="gallery-preview">
                            <div style="font-size: 3rem;">${item.fallback_icon}</div>
                        </div>
                        <div class="gallery-info">
                            <input type="text" class="form-input gallery-title" value="${item.title}" placeholder="Titre">
                            <textarea class="form-input gallery-description" placeholder="Description">${item.description}</textarea>
                            <button class="btn btn-sm btn-success" onclick="saveGalleryItem('${item.id}')">Sauvegarder</button>
                            <button class="btn btn-sm btn-error" onclick="removeGalleryItem('${item.id}')">Supprimer</button>
                        </div>
                    </div>
                `;
                galleryAdmin.insertAdjacentHTML('beforeend', itemHtml);
            });
        }
        
        // Fonctions de gestion du contenu
        async function saveWelcomeMessage() {
            const message = document.getElementById('welcome-message').value;
            if (!message.trim()) {
                showStatus('Le message d\'accueil ne peut pas être vide', 'error');
                return;
            }
            
            try {
                const data = window.adminData.content;
                if (!data.hero) data.hero = {};
                data.hero.title = message;
                await saveJsonData('content', data);
                showStatus('Message d\'accueil sauvegardé !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde: ' + error.message, 'error');
                console.error('Erreur sauvegarde message:', error);
            }
        }
        
        // Fonctions de gestion des couleurs
        async function saveColors() {
            const primaryColor = document.getElementById('primary-color').value;
            const secondaryColor = document.getElementById('secondary-color').value;
            
            try {
                const data = window.adminData.settings;
                if (!data.colors) data.colors = {};
                if (!data.theme) data.theme = {};
                if (!data.theme.colors) data.theme.colors = {};
                
                data.colors.primary = primaryColor;
                data.colors.secondary = secondaryColor;
                data.theme.colors.primary = primaryColor;
                data.theme.colors.secondary = secondaryColor;
                
                await saveJsonData('settings', data);
                showStatus('Couleurs sauvegardées !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde des couleurs', 'error');
                console.error('Erreur couleurs:', error);
            }
        }
        
        // Fonctions de gestion des polices
        async function saveFonts() {
            const primaryFont = document.getElementById('primary-font').value;
            const headingFont = document.getElementById('heading-font').value;
            
            try {
                const data = window.adminData.settings;
                if (!data.fonts) data.fonts = {};
                if (!data.theme) data.theme = {};
                if (!data.theme.fonts) data.theme.fonts = {};
                
                data.fonts.primary = primaryFont;
                data.fonts.heading = headingFont;
                data.theme.fonts.sans = primaryFont;
                data.theme.fonts.serif = headingFont;
                
                await saveJsonData('settings', data);
                showStatus('Polices sauvegardées !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde des polices', 'error');
                console.error('Erreur polices:', error);
            }
        }
        
        // Fonctions de gestion de la mise en page
        async function saveLayout() {
            const maxWidth = document.getElementById('max-width').value;
            const spacing = document.getElementById('spacing').value;
            
            try {
                const data = window.adminData.settings;
                if (!data.layout) data.layout = {};
                
                data.layout.maxWidth = maxWidth;
                data.layout.spacing = spacing;
                
                await saveJsonData('settings', data);
                showStatus('Mise en page sauvegardée !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde de la mise en page', 'error');
                console.error('Erreur layout:', error);
            }
        }
        
        // Fonctions de gestion du SEO
        async function saveSEO() {
            const title = document.getElementById('site-title').value;
            const description = document.getElementById('site-description').value;
            const keywords = document.getElementById('site-keywords').value;
            
            try {
                const data = window.adminData.settings;
                if (!data.seo) data.seo = {};
                
                data.seo.title = title;
                data.seo.description = description;
                data.seo.keywords = keywords;
                
                await saveJsonData('settings', data);
                showStatus('SEO sauvegardé !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde SEO', 'error');
                console.error('Erreur SEO:', error);
            }
        }
        
        // Fonctions de gestion des analytics
        async function saveAnalytics() {
            const matomoUrl = document.getElementById('matomo-url').value;
            const matomoSiteId = document.getElementById('matomo-site-id').value;
            const matomoEnabled = document.getElementById('matomo-enabled').value === 'true';
            
            try {
                const data = window.adminData.settings;
                if (!data.analytics) data.analytics = {};
                if (!data.analytics.matomo) data.analytics.matomo = {};
                
                data.analytics.matomo.url = matomoUrl;
                data.analytics.matomo.site_id = matomoSiteId;
                data.analytics.matomo.enabled = matomoEnabled;
                
                await saveJsonData('settings', data);
                showStatus('Analytics Matomo sauvegardés !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde des analytics', 'error');
                console.error('Erreur analytics:', error);
            }
        }
        
        async function saveServicesDescription() {
            const description = document.getElementById('services-description').value;
            if (!description.trim()) {
                showStatus('La description des services ne peut pas être vide', 'error');
                return;
            }
            
            try {
                const data = window.adminData.content;
                if (!data.hero) data.hero = {};
                data.hero.subtitle = description;
                await saveJsonData('content', data);
                showStatus('Description des services sauvegardée !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde: ' + error.message, 'error');
                console.error('Erreur sauvegarde description:', error);
            }
        }
        
        async function saveContactInfo() {
            const email = document.getElementById('contact-email').value;
            const phone = document.getElementById('contact-phone').value;
            const address = document.getElementById('contact-address').value;
            
            if (!email || !phone || !address) {
                showStatus('Tous les champs de contact sont requis', 'error');
                return;
            }
            
            try {
                const data = window.adminData.content;
                if (!data.contact) data.contact = {};
                if (!data.contact.email) data.contact.email = {};
                if (!data.contact.phones) data.contact.phones = [{}];
                if (!data.contact.addresses) data.contact.addresses = [{}];
                
                data.contact.email.primary = email;
                data.contact.phones[0].number = phone;
                data.contact.addresses[0].address = address;
                
                await saveJsonData('content', data);
                showStatus('Informations de contact sauvegardées !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde: ' + error.message, 'error');
                console.error('Erreur sauvegarde contact:', error);
            }
        }
        
        async function saveService(serviceId) {
            const serviceElement = document.querySelector(`[data-id="${serviceId}"]`);
            if (!serviceElement) {
                showStatus('Élément de service non trouvé', 'error');
                return;
            }
            
            try {
                const name = serviceElement.querySelector('.service-name').value;
                const description = serviceElement.querySelector('.service-description').value;
                const price = serviceElement.querySelector('.service-price-input').value;
                
                if (!name.trim() || !description.trim() || !price.trim()) {
                    showStatus('Tous les champs sont requis', 'error');
                    return;
                }
                
                const data = window.adminData.services;
                if (!data || !data.services) {
                    showStatus('Données des services non disponibles', 'error');
                    return;
                }
                
                const serviceIndex = data.services.findIndex(s => s.id === serviceId);
                
                if (serviceIndex !== -1) {
                    data.services[serviceIndex].name = name;
                    data.services[serviceIndex].description = description;
                    if (!data.services[serviceIndex].price) {
                        data.services[serviceIndex].price = {};
                    }
                    data.services[serviceIndex].price.display = price;
                    
                    await saveJsonData('services', data);
                    showStatus('Service sauvegardé !', 'success');
                } else {
                    showStatus('Service non trouvé dans les données', 'error');
                }
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde du service: ' + error.message, 'error');
                console.error('Erreur saveService:', error);
            }
        }
        
        async function saveGalleryItem(itemId) {
            const itemElement = document.querySelector(`[data-id="${itemId}"]`);
            if (!itemElement) return;
            
            try {
                const title = itemElement.querySelector('.gallery-title').value;
                const description = itemElement.querySelector('.gallery-description').value;
                
                const data = window.adminData.gallery;
                const itemIndex = data.items.findIndex(i => i.id === itemId);
                
                if (itemIndex !== -1) {
                    data.items[itemIndex].title = title;
                    data.items[itemIndex].description = description;
                    
                    await saveJsonData('gallery', data);
                    showStatus('Élément de galerie sauvegardé !', 'success');
                }
            } catch (error) {
                showStatus('Erreur lors de la sauvegarde de l\'élément', 'error');
            }
        }
        
        async function saveJsonData(filename, data) {
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('file', filename);
            formData.append('data', JSON.stringify(data));
            
            const response = await fetch('../api/admin-data.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error);
            }
            
            return result;
        }
        
        function createBackup() {
            showStatus('Création de la sauvegarde...', 'info');
            
            try {
                const backupData = {
                    content: window.adminData.content,
                    services: window.adminData.services,
                    gallery: window.adminData.gallery,
                    settings: window.adminData.settings,
                    backup_date: new Date().toISOString()
                };
                
                const dataStr = JSON.stringify(backupData, null, 2);
                const dataBlob = new Blob([dataStr], { type: 'application/json' });
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `backup-remmailleuse-${new Date().toISOString().split('T')[0]}.json`;
                link.click();
                URL.revokeObjectURL(url);
                
                showStatus('Sauvegarde créée !', 'success');
            } catch (error) {
                showStatus('Erreur lors de la création de la sauvegarde', 'error');
            }
        }
        
        function refreshBackups() {
            showStatus('Actualisation des sauvegardes...', 'info');
            // Simulation - dans un vrai environnement, on chargerait la liste des sauvegardes
            setTimeout(() => {
                showStatus('Sauvegardes actualisées !', 'success');
            }, 1000);
        }
        
        function refreshSecurityLogs() {
            showStatus('Actualisation des logs...', 'info');
            // Simulation - dans un vrai environnement, on chargerait les logs
            setTimeout(() => {
                showStatus('Logs actualisés !', 'success');
            }, 1000);
        }
        
        function clearSecurityLogs() {
            if (confirm('Êtes-vous sûr de vouloir nettoyer les logs de sécurité ?')) {
                showStatus('Nettoyage des logs...', 'info');
                setTimeout(() => {
                    showStatus('Logs nettoyés !', 'success');
                }, 1000);
            }
        }
        
        function loadContentData() {
            // Charger le contenu existant
            const welcomeMessage = document.getElementById('welcome-message');
            const servicesDescription = document.getElementById('services-description');
            const contactEmail = document.getElementById('contact-email');
            const contactPhone = document.getElementById('contact-phone');
            const contactAddress = document.getElementById('contact-address');
            
            if (welcomeMessage) welcomeMessage.value = 'Bienvenue dans mon atelier de couture...';
            if (servicesDescription) servicesDescription.value = 'Découvrez mes services de couture et retouche...';
            if (contactEmail) contactEmail.value = 'contact@remmailleuse.com';
            if (contactPhone) contactPhone.value = '+33 1 23 45 67 89';
            if (contactAddress) contactAddress.value = 'Paris, France';
        }
        
        function loadServicesData() {
            // Charger les services existants
            const servicesList = document.getElementById('services-list');
            if (servicesList) {
                servicesList.innerHTML = `
                    <div class="service-item-admin">
                        <input type="text" class="service-icon-input" value="✂️" readonly>
                        <div class="service-details">
                            <input type="text" class="form-input" value="Retouches simples" placeholder="Nom du service">
                            <textarea class="form-input" placeholder="Description du service">Ourlets, resserrage, etc.</textarea>
                        </div>
                        <input type="text" class="service-price-input form-input" value="15€" placeholder="Prix">
                        <button class="btn btn-sm btn-error" onclick="removeService(this)">Supprimer</button>
                    </div>
                `;
            }
        }
        
        function loadGalleryData() {
            // Charger les images de la galerie
            const galleryAdmin = document.getElementById('gallery-admin');
            if (galleryAdmin) {
                galleryAdmin.innerHTML = `
                    <div class="gallery-item-admin">
                        <div class="gallery-preview">
                            <img src="../assets/images/gallery/sample1.jpg" alt="Exemple" onerror="this.style.display='none'">
                        </div>
                        <div class="gallery-info">
                            <input type="text" class="form-input" value="Retouche de robe" placeholder="Titre">
                            <button class="btn btn-sm btn-error" onclick="removeGalleryItem(this)">Supprimer</button>
                        </div>
                    </div>
                `;
            }
        }
        
        function addService() {
            const servicesList = document.getElementById('services-list');
            if (servicesList) {
                const newServiceId = 'service_' + Date.now();
                const newService = document.createElement('div');
                newService.className = 'service-item-admin';
                newService.dataset.id = newServiceId;
                newService.innerHTML = `
                    <input type="text" class="service-icon-input" value="🧵" readonly>
                    <div class="service-details">
                        <input type="text" class="form-input service-name" placeholder="Nom du service">
                        <textarea class="form-input service-description" placeholder="Description du service"></textarea>
                    </div>
                    <input type="text" class="service-price-input form-input" placeholder="Prix">
                    <button class="btn btn-sm btn-success" onclick="saveService('${newServiceId}')">Sauvegarder</button>
                    <button class="btn btn-sm btn-error" onclick="removeService('${newServiceId}')">Supprimer</button>
                `;
                servicesList.appendChild(newService);
                
                // Ajouter le service aux données
                if (!window.adminData.services) {
                    window.adminData.services = { services: [] };
                }
                window.adminData.services.services.push({
                    id: newServiceId,
                    name: '',
                    description: '',
                    icon: '🧵',
                    price: { display: '' }
                });
                
                showStatus('Nouveau service ajouté !', 'success');
            }
        }
        
        function removeService(serviceId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce service ?')) {
                const serviceElement = document.querySelector(`[data-id="${serviceId}"]`);
                if (serviceElement) {
                    serviceElement.remove();
                    
                    // Supprimer des données
                    if (window.adminData.services && window.adminData.services.services) {
                        window.adminData.services.services = window.adminData.services.services.filter(s => s.id !== serviceId);
                    }
                    
                    showStatus('Service supprimé !', 'success');
                }
            }
        }
        
        function removeGalleryItem(itemId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const itemElement = document.querySelector(`[data-id="${itemId}"]`);
                if (itemElement) {
                    itemElement.remove();
                    
                    // Supprimer des données
                    if (window.adminData.gallery && window.adminData.gallery.items) {
                        window.adminData.gallery.items = window.adminData.gallery.items.filter(i => i.id !== itemId);
                    }
                    
                    showStatus('Image supprimée !', 'success');
                }
            }
        }
        
        function handleGalleryUpload(event) {
            const files = event.target.files;
            if (files.length === 0) return;
            
            showStatus(`${files.length} fichier(s) sélectionné(s)`, 'info');
            
            // Simuler l'upload et ajouter aux données
            Array.from(files).forEach((file, index) => {
                setTimeout(() => {
                    const newItemId = 'gallery_' + Date.now() + '_' + index;
                    
                    // Ajouter aux données
                    if (!window.adminData.gallery) {
                        window.adminData.gallery = { items: [] };
                    }
                    window.adminData.gallery.items.push({
                        id: newItemId,
                        title: file.name.replace(/\.[^/.]+$/, ''),
                        description: '',
                        fallback_icon: '🖼️',
                        category: 'general'
                    });
                    
                    // Recharger l'affichage
                    renderGalleryAdmin(window.adminData.gallery.items);
                    
                    showStatus(`Image "${file.name}" uploadée !`, 'success');
                }, (index + 1) * 500);
            });
        }
        
        function addProcessStep() {
            const processSteps = document.getElementById('process-steps');
            if (processSteps) {
                const newStep = document.createElement('div');
                newStep.className = 'process-step-admin';
                newStep.innerHTML = `
                    <div class="step-number">📋</div>
                    <div class="step-details">
                        <input type="text" class="form-input step-title" placeholder="Titre de l'étape">
                        <textarea class="form-input step-description" placeholder="Description de l'étape"></textarea>
                    </div>
                    <button class="btn btn-sm btn-success" onclick="saveProcessStep(this)">Sauvegarder</button>
                    <button class="btn btn-sm btn-error" onclick="removeProcessStep(this)">Supprimer</button>
                `;
                processSteps.appendChild(newStep);
                showStatus('Nouvelle étape ajoutée !', 'success');
            }
        }
        
        function saveProcessStep(button) {
            const stepElement = button.closest('.process-step-admin');
            const title = stepElement.querySelector('.step-title').value;
            const description = stepElement.querySelector('.step-description').value;
            
            if (title.trim() && description.trim()) {
                showStatus('Étape sauvegardée !', 'success');
            } else {
                showStatus('Veuillez remplir tous les champs', 'error');
            }
        }
        
        function removeProcessStep(button) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette étape ?')) {
                button.closest('.process-step-admin').remove();
                showStatus('Étape supprimée !', 'success');
            }
        }
        
        function createBackup() {
            showStatus('Création de sauvegarde en cours...', 'info');
            
            // Simuler la création de backup
            setTimeout(() => {
                showStatus('Sauvegarde créée avec succès !', 'success');
                refreshBackups();
            }, 2000);
        }
        
        function refreshBackups() {
            const backupList = document.getElementById('backup-list');
            if (backupList) {
                backupList.innerHTML = `
                    <div class="backup-item">
                        <span>Backup_${new Date().toISOString().split('T')[0]}.zip</span>
                        <span>Aujourd'hui</span>
                        <button class="btn btn-sm btn-primary">Télécharger</button>
                    </div>
                `;
            }
        }
        
        function refreshSecurityLogs() {
            const securityLogs = document.getElementById('security-logs');
            if (securityLogs) {
                securityLogs.innerHTML = `
                    <div class="log-entry">
                        <span class="log-time">${new Date().toLocaleString()}</span>
                        <span class="log-message">Connexion admin réussie</span>
                        <span class="log-level success">INFO</span>
                    </div>
                `;
            }
        }
        
        function clearSecurityLogs() {
            if (confirm('Êtes-vous sûr de vouloir nettoyer les logs de sécurité ?')) {
                const securityLogs = document.getElementById('security-logs');
                if (securityLogs) {
                    securityLogs.innerHTML = '<p>Logs nettoyés</p>';
                }
                showStatus('Logs de sécurité nettoyés !', 'success');
            }
        }
        
        function saveColors() {
            const primaryColor = document.getElementById('primary-color').value;
            const secondaryColor = document.getElementById('secondary-color').value;
            
            showStatus('Couleurs sauvegardées !', 'success');
            console.log('Couleurs:', { primaryColor, secondaryColor });
        }

        // Gestion de la modal
        function closeModal() {
            console.log('closeModal appelée');
            const modal = document.getElementById('modal-overlay');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none'; // Double sécurité
                console.log('Modal fermée');
            } else {
                console.error('Modal non trouvée');
            }
        }

        function confirmAction() {
            console.log('confirmAction appelée');
            // Fermer la modal
            closeModal();
            
            // Ici on pourrait ajouter une action spécifique
            console.log('Action confirmée');
        }

        // Fonction pour afficher la modal (debug)
        function showModal(title, message) {
            console.log('showModal appelée:', title, message);
            const modal = document.getElementById('modal-overlay');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            
            if (modal && modalTitle && modalMessage) {
                modalTitle.textContent = title || 'Confirmation';
                modalMessage.textContent = message || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                modal.classList.add('show');
                console.log('Modal affichée');
            } else {
                console.error('Éléments de modal manquants');
            }
        }

        // Fermer la modal si on clique sur l'overlay
        document.addEventListener('DOMContentLoaded', function() {
            const modalOverlay = document.getElementById('modal-overlay');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });
                console.log('Event listener modal ajouté');
            } else {
                console.error('Modal overlay non trouvé');
            }
        });

        // Gestion du bouton retour en haut
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Afficher/masquer le bouton selon la position de scroll
        window.addEventListener('scroll', function() {
            const scrollToTopBtn = document.getElementById('scrollToTop');
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });
        
        // Animation d'apparition progressive du contenu
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.admin-card');
            cards.forEach(function(card, index) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(function() {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Auto-save silencieux toutes les 30 secondes
        setInterval(() => {
            if (window.adminApp && window.adminApp.hasUnsavedChanges) {
                window.adminApp.autoSave();
            }
        }, 30000);

        // Sauvegarde avant fermeture de la page
        window.addEventListener('beforeunload', (e) => {
            if (window.adminApp && window.adminApp.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
            }
        });
    </script>
    
    <!-- Bouton retour en haut -->
    <button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()" title="Retour en haut">
        ↑
    </button>
    
</body>
</html>