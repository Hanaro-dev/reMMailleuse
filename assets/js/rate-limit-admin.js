/**
 * ===== INTERFACE ADMIN RATE LIMITING - SITE REMMAILLEUSE =====
 * Interface d'administration pour le système de rate limiting
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class RateLimitAdmin {
    constructor() {
        this.apiUrl = '/api/rate-limit-admin.php';
        this.refreshInterval = 30000; // 30 secondes
        this.refreshTimer = null;
        this.currentView = 'dashboard';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadDashboard();
        this.startAutoRefresh();
    }
    
    setupEventListeners() {
        // Navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action]')) {
                this.handleAction(e.target.dataset.action, e.target);
            }
        });
        
        // Formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.rate-limit-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });
        
        // Refresh manuel
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-refresh]')) {
                this.refresh();
            }
        });
        
        // Filtres
        document.addEventListener('change', (e) => {
            if (e.target.matches('[data-filter]')) {
                this.handleFilter(e.target);
            }
        });
    }
    
    async handleAction(action, element) {
        try {
            switch (action) {
                case 'view-dashboard':
                    await this.loadDashboard();
                    break;
                case 'view-detailed':
                    await this.loadDetailedReport();
                    break;
                case 'check-ip':
                    await this.checkIP();
                    break;
                case 'unblock-ip':
                    await this.unblockIP(element);
                    break;
                case 'add-whitelist':
                    await this.addToWhitelist();
                    break;
                case 'remove-whitelist':
                    await this.removeFromWhitelist(element);
                    break;
                case 'add-blacklist':
                    await this.addToBlacklist();
                    break;
                case 'remove-blacklist':
                    await this.removeFromBlacklist(element);
                    break;
                case 'cleanup':
                    await this.cleanup();
                    break;
                case 'reset-all':
                    await this.resetAll();
                    break;
                default:
                    console.warn('Action inconnue:', action);
            }
        } catch (error) {
            this.showError('Erreur lors de l\'action: ' + error.message);
        }
    }
    
    async loadDashboard() {
        this.currentView = 'dashboard';
        this.showLoading();
        
        try {
            const response = await fetch(`${this.apiUrl}?action=stats`);
            const result = await response.json();
            
            if (result.success) {
                this.renderDashboard(result.data);
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors du chargement du dashboard: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    async loadDetailedReport() {
        this.currentView = 'detailed';
        this.showLoading();
        
        try {
            const response = await fetch(`${this.apiUrl}?action=detailed_report`);
            const result = await response.json();
            
            if (result.success) {
                this.renderDetailedReport(result.data);
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors du chargement du rapport: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    async checkIP() {
        const ip = document.getElementById('check-ip-input').value.trim();
        const rule = document.getElementById('check-ip-rule').value;
        
        if (!ip) {
            this.showError('Veuillez saisir une adresse IP');
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=check_ip&ip=${encodeURIComponent(ip)}&rule=${encodeURIComponent(rule)}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayIPStats(result.data);
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de la vérification: ' + error.message);
        }
    }
    
    async unblockIP(element) {
        const ip = element.dataset.ip;
        const rule = element.dataset.rule || 'general';
        
        if (!confirm(`Êtes-vous sûr de vouloir débloquer l'IP ${ip} pour la règle ${rule} ?`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'unblock_ip');
            formData.append('ip', ip);
            formData.append('rule', rule);
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors du déblocage: ' + error.message);
        }
    }
    
    async addToWhitelist() {
        const ip = document.getElementById('whitelist-ip-input').value.trim();
        
        if (!ip) {
            this.showError('Veuillez saisir une adresse IP');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'add_whitelist');
            formData.append('ip', ip);
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                document.getElementById('whitelist-ip-input').value = '';
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de l\'ajout: ' + error.message);
        }
    }
    
    async removeFromWhitelist(element) {
        const ip = element.dataset.ip;
        
        if (!confirm(`Êtes-vous sûr de vouloir retirer ${ip} de la whitelist ?`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'remove_whitelist');
            formData.append('ip', ip);
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression: ' + error.message);
        }
    }
    
    async addToBlacklist() {
        const ip = document.getElementById('blacklist-ip-input').value.trim();
        const reason = document.getElementById('blacklist-reason-input').value.trim();
        
        if (!ip) {
            this.showError('Veuillez saisir une adresse IP');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'add_blacklist');
            formData.append('ip', ip);
            formData.append('reason', reason || 'admin_manual');
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                document.getElementById('blacklist-ip-input').value = '';
                document.getElementById('blacklist-reason-input').value = '';
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de l\'ajout: ' + error.message);
        }
    }
    
    async removeFromBlacklist(element) {
        const ip = element.dataset.ip;
        
        if (!confirm(`Êtes-vous sûr de vouloir retirer ${ip} de la blacklist ?`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'remove_blacklist');
            formData.append('ip', ip);
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression: ' + error.message);
        }
    }
    
    async cleanup() {
        if (!confirm('Êtes-vous sûr de vouloir nettoyer les anciens fichiers de rate limiting ?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'cleanup');
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors du nettoyage: ' + error.message);
        }
    }
    
    async resetAll() {
        const confirmation = prompt('ATTENTION: Cette action va réinitialiser TOUTES les limites de rate limiting.\\n\\nTapez "RESET_ALL_LIMITS" pour confirmer:');
        
        if (confirmation !== 'RESET_ALL_LIMITS') {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'reset_all');
            formData.append('confirm', 'RESET_ALL_LIMITS');
            formData.append('csrf_token', await this.getCSRFToken());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.refresh();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            this.showError('Erreur lors de la réinitialisation: ' + error.message);
        }
    }
    
    renderDashboard(data) {
        const container = document.getElementById('rate-limit-content');
        
        const html = `
            <div class="rate-limit-dashboard">
                <div class="dashboard-header">
                    <h2>📊 Tableau de bord Rate Limiting</h2>
                    <button class="btn btn-secondary" data-refresh>🔄 Actualiser</button>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Requêtes totales</h3>
                        <div class="stat-value">${data.global.total_requests}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Requêtes bloquées</h3>
                        <div class="stat-value error">${data.global.blocked_requests}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Requêtes autorisées</h3>
                        <div class="stat-value success">${data.global.allowed_requests}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Délais progressifs</h3>
                        <div class="stat-value warning">${data.global.progressive_delays}</div>
                    </div>
                </div>
                
                <div class="rules-section">
                    <h3>📋 Règles configurées</h3>
                    <div class="rules-grid">
                        ${Object.entries(data.rules).map(([name, rule]) => `
                            <div class="rule-card">
                                <h4>${name}</h4>
                                <div class="rule-details">
                                    <span>Limite: ${rule.limit}</span>
                                    <span>Fenêtre: ${rule.window}s</span>
                                    <span>Progressif: ${rule.progressive ? 'Oui' : 'Non'}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="lists-section">
                    <div class="list-container">
                        <h3>✅ Whitelist (${data.whitelist_count})</h3>
                        <div class="whitelist-controls">
                            <input type="text" id="whitelist-ip-input" placeholder="Adresse IP">
                            <button class="btn btn-primary" data-action="add-whitelist">Ajouter</button>
                        </div>
                    </div>
                    
                    <div class="list-container">
                        <h3>❌ Blacklist (${data.blacklist_count})</h3>
                        <div class="blacklist-controls">
                            <input type="text" id="blacklist-ip-input" placeholder="Adresse IP">
                            <input type="text" id="blacklist-reason-input" placeholder="Raison (optionnel)">
                            <button class="btn btn-danger" data-action="add-blacklist">Ajouter</button>
                        </div>
                    </div>
                </div>
                
                <div class="tools-section">
                    <h3>🔧 Outils</h3>
                    <div class="tools-grid">
                        <div class="tool-card">
                            <h4>Vérifier une IP</h4>
                            <input type="text" id="check-ip-input" placeholder="Adresse IP">
                            <select id="check-ip-rule">
                                <option value="general">Général</option>
                                <option value="auth">Authentification</option>
                                <option value="contact">Contact</option>
                                <option value="upload">Upload</option>
                                <option value="api">API</option>
                            </select>
                            <button class="btn btn-info" data-action="check-ip">Vérifier</button>
                        </div>
                        
                        <div class="tool-card">
                            <h4>Actions</h4>
                            <button class="btn btn-warning" data-action="cleanup">🧹 Nettoyage</button>
                            <button class="btn btn-danger" data-action="reset-all">🔄 Reset complet</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    renderDetailedReport(data) {
        const container = document.getElementById('rate-limit-content');
        
        const html = `
            <div class="rate-limit-detailed">
                <div class="detailed-header">
                    <h2>📈 Rapport détaillé</h2>
                    <button class="btn btn-secondary" data-action="view-dashboard">← Retour</button>
                </div>
                
                <div class="active-limits-section">
                    <h3>🔒 Limites actives</h3>
                    <div class="active-limits-grid">
                        ${data.active_limits.map(limit => `
                            <div class="limit-card ${limit.is_blocked ? 'blocked' : ''}">
                                <h4>${limit.key}</h4>
                                <div class="limit-details">
                                    <span>Requêtes: ${limit.requests_count}</span>
                                    <span>Bloqué: ${limit.is_blocked ? 'Oui' : 'Non'}</span>
                                    ${limit.blocked_until ? `<span>Jusqu'à: ${new Date(limit.blocked_until * 1000).toLocaleString()}</span>` : ''}
                                </div>
                                ${limit.is_blocked ? `<button class="btn btn-sm btn-warning" data-action="unblock-ip" data-ip="${limit.key}">Débloquer</button>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="recent-blocks-section">
                    <h3>🚫 Blocages récents</h3>
                    <div class="recent-blocks-list">
                        ${data.recent_blocks.map(block => `
                            <div class="block-item">
                                <span class="block-key">${block.key}</span>
                                <span class="block-count">${block.block_count} blocages</span>
                                <span class="block-until">Jusqu'à ${new Date(block.blocked_until * 1000).toLocaleString()}</span>
                                <button class="btn btn-sm btn-warning" data-action="unblock-ip" data-ip="${block.key}">Débloquer</button>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="top-blocked-section">
                    <h3>🔥 IPs les plus bloquées</h3>
                    <div class="top-blocked-list">
                        ${Object.entries(data.top_blocked_ips).map(([ip, count]) => `
                            <div class="blocked-item">
                                <span class="blocked-ip">${ip}</span>
                                <span class="blocked-count">${count} blocages</span>
                                <button class="btn btn-sm btn-danger" data-action="add-blacklist" data-ip="${ip}">Blacklister</button>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="lists-detailed-section">
                    <div class="list-detailed">
                        <h3>✅ Whitelist</h3>
                        <div class="list-items">
                            ${data.whitelist.map(ip => `
                                <div class="list-item">
                                    <span>${ip}</span>
                                    <button class="btn btn-sm btn-danger" data-action="remove-whitelist" data-ip="${ip}">Retirer</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="list-detailed">
                        <h3>❌ Blacklist</h3>
                        <div class="list-items">
                            ${data.blacklist.map(ip => `
                                <div class="list-item">
                                    <span>${ip}</span>
                                    <button class="btn btn-sm btn-success" data-action="remove-blacklist" data-ip="${ip}">Retirer</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    displayIPStats(data) {
        const container = document.getElementById('ip-stats-result');
        
        const html = `
            <div class="ip-stats">
                <h4>📊 Statistiques pour ${data.identifier}</h4>
                <div class="stats-details">
                    <div class="stat-row">
                        <span>Règle:</span>
                        <span>${data.rule}</span>
                    </div>
                    <div class="stat-row">
                        <span>Requêtes actuelles:</span>
                        <span>${data.current_count}/${data.limit}</span>
                    </div>
                    <div class="stat-row">
                        <span>Restantes:</span>
                        <span>${data.remaining}</span>
                    </div>
                    <div class="stat-row">
                        <span>Fenêtre:</span>
                        <span>${data.window}s</span>
                    </div>
                    <div class="stat-row">
                        <span>Total requêtes:</span>
                        <span>${data.total_requests}</span>
                    </div>
                    <div class="stat-row">
                        <span>Nombre de blocages:</span>
                        <span>${data.block_count}</span>
                    </div>
                    ${data.blocked_until ? `
                        <div class="stat-row error">
                            <span>Bloqué jusqu'à:</span>
                            <span>${new Date(data.blocked_until * 1000).toLocaleString()}</span>
                        </div>
                    ` : ''}
                    ${data.last_request ? `
                        <div class="stat-row">
                            <span>Dernière requête:</span>
                            <span>${new Date(data.last_request * 1000).toLocaleString()}</span>
                        </div>
                    ` : ''}
                </div>
                ${data.blocked_until ? `
                    <button class="btn btn-warning" data-action="unblock-ip" data-ip="${data.identifier}" data-rule="${data.rule}">
                        Débloquer cette IP
                    </button>
                ` : ''}
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    async getCSRFToken() {
        // Récupérer le token CSRF depuis l'API
        const response = await fetch('/api/csrf.php?action=get_token');
        const result = await response.json();
        return result.token;
    }
    
    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.refresh();
        }, this.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
    
    refresh() {
        if (this.currentView === 'dashboard') {
            this.loadDashboard();
        } else if (this.currentView === 'detailed') {
            this.loadDetailedReport();
        }
    }
    
    showLoading() {
        const container = document.getElementById('rate-limit-content');
        container.innerHTML = '<div class="loading">Chargement...</div>';
    }
    
    hideLoading() {
        // Le loading sera remplacé par le contenu
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    destroy() {
        this.stopAutoRefresh();
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('rate-limit-content')) {
        window.rateLimitAdmin = new RateLimitAdmin();
    }
});

// Nettoyage avant déchargement
window.addEventListener('beforeunload', () => {
    if (window.rateLimitAdmin) {
        window.rateLimitAdmin.destroy();
    }
});