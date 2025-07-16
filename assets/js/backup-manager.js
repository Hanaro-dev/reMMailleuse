/**
 * Gestionnaire de sauvegardes côté client
 * Interface pour créer, restaurer et gérer les sauvegardes
 */

class BackupManager {
    constructor() {
        this.apiUrl = '../api/backup.php';
        this.backups = [];
        this.isLoading = false;
        
        this.init();
    }
    
    async init() {
        // Attendre que le token CSRF soit prêt
        if (window.csrfManager && !window.csrfManager.isReady()) {
            await window.csrfManager.refreshToken();
        }
        
        this.createBackupInterface();
        this.loadBackups();
        this.setupEventListeners();
        this.setupAutoBackup();
    }
    
    createBackupInterface() {
        const container = document.getElementById('backup-section');
        if (!container) return;
        
        container.innerHTML = `
            <div class="backup-manager">
                <div class="backup-header">
                    <h3>Gestion des sauvegardes</h3>
                    <div class="backup-actions">
                        <button id="create-backup-btn" class="btn btn-primary">
                            💾 Créer une sauvegarde
                        </button>
                        <button id="refresh-backups-btn" class="btn btn-outline">
                            🔄 Actualiser
                        </button>
                    </div>
                </div>
                
                <div class="backup-stats" id="backup-stats">
                    <div class="stats-loading">Chargement des statistiques...</div>
                </div>
                
                <div class="backup-list" id="backup-list">
                    <div class="backup-loading">Chargement des sauvegardes...</div>
                </div>
            </div>
        `;
    }
    
    setupEventListeners() {
        const createBtn = document.getElementById('create-backup-btn');
        const refreshBtn = document.getElementById('refresh-backups-btn');
        
        if (createBtn) {
            createBtn.addEventListener('click', () => this.createBackup());
        }
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadBackups());
        }
    }
    
    setupAutoBackup() {
        // Vérifier périodiquement si une sauvegarde auto est nécessaire
        setInterval(() => {
            this.checkAutoBackup();
        }, 5 * 60 * 1000); // Toutes les 5 minutes
        
        // Sauvegarde automatique au démarrage
        this.checkAutoBackup();
    }
    
    async checkAutoBackup() {
        try {
            const response = await fetch(`${this.apiUrl}?action=auto`);
            const data = await response.json();
            
            if (data.success && data.auto_backup && !data.auto_backup.skipped) {
                this.showNotification('Sauvegarde automatique créée', 'success');
                this.loadBackups(); // Recharger la liste
            }
        } catch (error) {
            // Erreur silencieuse pour l'auto-backup
        }
    }
    
    async createBackup() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        const createBtn = document.getElementById('create-backup-btn');
        const originalText = createBtn.textContent;
        
        createBtn.textContent = '⏳ Création...';
        createBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('type', 'manual');
            
            // Ajouter le token CSRF
            if (window.csrfManager && window.csrfManager.isReady()) {
                formData.append('csrf_token', window.csrfManager.getToken());
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Sauvegarde créée avec succès', 'success');
                this.loadBackups(); // Recharger la liste
            } else {
                this.showNotification(`Erreur: ${data.error}`, 'error');
            }
            
        } catch (error) {
            this.showNotification('Erreur lors de la création de la sauvegarde', 'error');
        } finally {
            this.isLoading = false;
            createBtn.textContent = originalText;
            createBtn.disabled = false;
        }
    }
    
    async loadBackups() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list`);
            const data = await response.json();
            
            if (data.success) {
                this.backups = data.backups;
                this.renderBackupList();
            } else {
                this.showError('Erreur lors du chargement des sauvegardes');
            }
        } catch (error) {
            this.showError('Erreur de connexion');
        }
        
        // Charger les statistiques
        this.loadStats();
    }
    
    async loadStats() {
        try {
            const response = await fetch(`${this.apiUrl}?action=stats`);
            const data = await response.json();
            
            if (data.success) {
                this.renderStats(data.stats);
            }
        } catch (error) {
            // Erreur silencieuse pour les stats
        }
    }
    
    renderStats(stats) {
        const container = document.getElementById('backup-stats');
        if (!container) return;
        
        container.innerHTML = `
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">${stats.total_backups}</div>
                    <div class="stat-label">Sauvegardes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.total_size_formatted}</div>
                    <div class="stat-label">Taille totale</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.types.auto || 0}</div>
                    <div class="stat-label">Automatiques</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.types.manual || 0}</div>
                    <div class="stat-label">Manuelles</div>
                </div>
            </div>
        `;
    }
    
    renderBackupList() {
        const container = document.getElementById('backup-list');
        if (!container) return;
        
        if (this.backups.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <div class="empty-text">Aucune sauvegarde disponible</div>
                    <div class="empty-subtext">Créez votre première sauvegarde</div>
                </div>
            `;
            return;
        }
        
        const backupItems = this.backups.map(backup => this.createBackupItem(backup)).join('');
        
        container.innerHTML = `
            <div class="backup-items">
                ${backupItems}
            </div>
        `;
        
        // Ajouter les event listeners aux boutons
        this.setupBackupItemListeners();
    }
    
    createBackupItem(backup) {
        const date = new Date(backup.timestamp * 1000);
        const formattedDate = date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const typeIcon = backup.type === 'auto' ? '🤖' : '👤';
        const typeText = backup.type === 'auto' ? 'Automatique' : 'Manuelle';
        
        return `
            <div class="backup-item" data-backup-id="${backup.id}">
                <div class="backup-info">
                    <div class="backup-title">
                        <span class="backup-type">${typeIcon} ${typeText}</span>
                        <span class="backup-date">${formattedDate}</span>
                    </div>
                    <div class="backup-details">
                        <span class="backup-files">${backup.files.length} fichiers</span>
                        <span class="backup-size">${this.formatBytes(backup.size)}</span>
                    </div>
                </div>
                <div class="backup-actions">
                    <button class="btn btn-outline btn-sm restore-btn" data-backup-id="${backup.id}">
                        🔄 Restaurer
                    </button>
                    <button class="btn btn-outline btn-sm delete-btn" data-backup-id="${backup.id}">
                        🗑️ Supprimer
                    </button>
                </div>
            </div>
        `;
    }
    
    setupBackupItemListeners() {
        const restoreButtons = document.querySelectorAll('.restore-btn');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        restoreButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const backupId = e.target.dataset.backupId;
                this.confirmRestore(backupId);
            });
        });
        
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const backupId = e.target.dataset.backupId;
                this.confirmDelete(backupId);
            });
        });
    }
    
    confirmRestore(backupId) {
        if (confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Cette action remplacera les données actuelles.')) {
            this.restoreBackup(backupId);
        }
    }
    
    confirmDelete(backupId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde ? Cette action est irréversible.')) {
            this.deleteBackup(backupId);
        }
    }
    
    async restoreBackup(backupId) {
        try {
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('backup_id', backupId);
            
            // Ajouter le token CSRF
            if (window.csrfManager && window.csrfManager.isReady()) {
                formData.append('csrf_token', window.csrfManager.getToken());
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Sauvegarde restaurée avec succès', 'success');
                // Recharger la page pour voir les changements
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                this.showNotification(`Erreur: ${data.error}`, 'error');
            }
            
        } catch (error) {
            this.showNotification('Erreur lors de la restauration', 'error');
        }
    }
    
    async deleteBackup(backupId) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('backup_id', backupId);
            
            // Ajouter le token CSRF
            if (window.csrfManager && window.csrfManager.isReady()) {
                formData.append('csrf_token', window.csrfManager.getToken());
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Sauvegarde supprimée', 'success');
                this.loadBackups(); // Recharger la liste
            } else {
                this.showNotification(`Erreur: ${data.error}`, 'error');
            }
            
        } catch (error) {
            this.showNotification('Erreur lors de la suppression', 'error');
        }
    }
    
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
    
    showNotification(message, type = 'info') {
        // Utiliser le système de notification existant si disponible
        if (window.adminApp && window.adminApp.showStatus) {
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            window.adminApp.showStatus(`${icon} ${message}`, type);
        } else {
            // Fallback vers alert
            alert(message);
        }
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
}

// Styles CSS pour les sauvegardes
const backupStyles = `
<style>
.backup-manager {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.backup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.backup-header h3 {
    margin: 0;
    color: var(--color-dark);
}

.backup-actions {
    display: flex;
    gap: 0.5rem;
}

.backup-stats {
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: var(--color-neutral);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.backup-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.backup-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    transition: all 0.3s ease;
}

.backup-item:hover {
    border-color: var(--color-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.backup-info {
    flex: 1;
}

.backup-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.25rem;
}

.backup-type {
    font-weight: 500;
    color: var(--color-primary);
}

.backup-date {
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.backup-details {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.backup-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--color-text-light);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-text {
    font-size: 1.2rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.empty-subtext {
    font-size: 0.875rem;
}

.stats-loading,
.backup-loading {
    text-align: center;
    padding: 2rem;
    color: var(--color-text-light);
}

@media (max-width: 768px) {
    .backup-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .backup-actions {
        justify-content: center;
    }
    
    .backup-item {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .backup-actions {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
`;

// Injecter les styles
if (!document.querySelector('#backup-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'backup-styles';
    styleElement.innerHTML = backupStyles;
    document.head.appendChild(styleElement);
}

// Initialiser automatiquement si l'élément est présent
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('backup-section')) {
        window.backupManager = new BackupManager();
    }
});

// Export pour les modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BackupManager;
}

window.BackupManager = BackupManager;