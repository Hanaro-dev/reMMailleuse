# Système de Sauvegarde Automatique - ReMmailleuse

## Vue d'ensemble

Le système de sauvegarde automatique protège les données JSON du site avec des sauvegardes horodatées, une rotation intelligente et une interface de gestion complète.

## Fonctionnalités

### 💾 Sauvegarde complète
- **Sauvegarde automatique** toutes les heures
- **Sauvegarde manuelle** à la demande
- **Compression ZIP** pour économiser l'espace
- **Métadonnées détaillées** pour chaque sauvegarde
- **Rotation intelligente** (max 50 sauvegardes, 30 jours)

### 🔄 Restauration
- **Restauration complète** depuis l'interface admin
- **Prévisualisation** des sauvegardes
- **Validation** avant restauration
- **Sécurité** avec authentification admin

### 📊 Monitoring
- **Statistiques** en temps réel
- **Taille des sauvegardes** trackée
- **Types de sauvegarde** (auto/manuelle)
- **Nettoyage automatique** des anciennes sauvegardes

## Architecture

### Structure des fichiers

```
api/
├── BackupManager.php    # Classe principale de gestion
├── backup.php          # API endpoint pour les sauvegardes
└── [autres APIs]       # Intégration avec les autres APIs

assets/js/
└── backup-manager.js   # Interface client de gestion

backups/
├── .htaccess          # Sécurité (accès interdit)
├── 2025-01-15_14-30-00_auto.json     # Métadonnées
├── 2025-01-15_14-30-00_auto.zip      # Archive compressée
└── [autres sauvegardes]

admin/
└── index.html         # Interface admin avec section sauvegardes
```

## Classe BackupManager

### Méthodes principales

```php
class BackupManager {
    // Création de sauvegardes
    public function createBackup($type = 'auto')
    public function autoBackup()
    public function needsBackup()
    
    // Gestion des sauvegardes
    public function listBackups()
    public function restoreBackup($backupId)
    public function deleteBackup($backupId)  // private
    
    // Statistiques
    public function getBackupStats()
    
    // Utilitaires
    private function backupFile($filePath, $backupId)
    private function createCompressedBackup($backupId, $backup)
    private function cleanupOldBackups()
}
```

### Configuration

```php
$this->config = [
    'max_backups' => 50,           // Nombre max de sauvegardes
    'backup_interval' => 3600,     // Intervalle en secondes (1h)
    'compression' => true,         // Activer la compression ZIP
    'rotate_days' => 30,           // Rotation après X jours
    'files_to_backup' => [
        'content.json',
        'gallery.json', 
        'services.json',
        'settings.json'
    ]
];
```

## API Endpoints

### GET /api/backup.php

**Actions publiques (sans auth) :**
- `?action=auto` : Sauvegarde automatique si nécessaire
- `?action=stats` : Statistiques des sauvegardes

**Actions admin (avec auth) :**
- `?action=list` : Liste toutes les sauvegardes

### POST /api/backup.php

**Actions requérant authentification + CSRF :**

#### Créer une sauvegarde
```javascript
POST /api/backup.php
{
    "action": "create",
    "type": "manual",
    "csrf_token": "abc123..."
}
```

**Réponse :**
```json
{
    "success": true,
    "message": "Sauvegarde créée avec succès",
    "backup": {
        "backup_id": "2025-01-15_14-30-00_manual",
        "files_count": 4,
        "total_size": 52428,
        "compressed": true
    }
}
```

#### Restaurer une sauvegarde
```javascript
POST /api/backup.php
{
    "action": "restore",
    "backup_id": "2025-01-15_14-30-00_manual",
    "csrf_token": "abc123..."
}
```

#### Supprimer une sauvegarde
```javascript
POST /api/backup.php
{
    "action": "delete",
    "backup_id": "2025-01-15_14-30-00_manual",
    "csrf_token": "abc123..."
}
```

## Structure des sauvegardes

### Fichier de métadonnées (.json)
```json
{
    "id": "2025-01-15_14-30-00_auto",
    "timestamp": 1642258200,
    "type": "auto",
    "size": 52428,
    "files": [
        {
            "filename": "content.json",
            "backup_path": "/backups/2025-01-15_14-30-00_auto_content.json",
            "size": 15620,
            "hash": "sha256:abc123...",
            "last_modified": 1642258100
        }
    ],
    "metadata": {
        "created_at": "2025-01-15T14:30:00+01:00",
        "server": "localhost",
        "user_agent": "Mozilla/5.0...",
        "ip": "127.0.0.1"
    }
}
```

### Archive compressée (.zip)
```
2025-01-15_14-30-00_auto.zip
├── content.json
├── gallery.json
├── services.json
├── settings.json
└── backup_info.json
```

## Interface client (JavaScript)

### Utilisation

```javascript
// Initialisation automatique
const backupManager = new BackupManager();

// Méthodes disponibles
backupManager.createBackup();           // Créer une sauvegarde
backupManager.loadBackups();           // Charger la liste
backupManager.restoreBackup(id);       // Restaurer
backupManager.deleteBackup(id);        // Supprimer
backupManager.checkAutoBackup();       // Vérifier auto-backup
```

### Interface admin

L'interface admin inclut :
- **Statistiques** en temps réel
- **Liste des sauvegardes** avec dates et tailles
- **Boutons d'action** (créer, restaurer, supprimer)
- **Confirmations** pour les actions critiques
- **Notifications** de succès/erreur

## Sécurité

### Authentification
- **Sessions PHP** pour l'accès admin
- **Tokens CSRF** pour toutes les actions POST
- **Validation des permissions** sur chaque action

### Protection des fichiers
```apache
# /backups/.htaccess
Require all denied
Options -Indexes
```

### Validation des données
- **Vérification des IDs** de sauvegarde
- **Sanitisation des entrées**
- **Contrôle d'accès** strict

## Automatisation

### Déclenchement automatique

1. **Probabilité dans backup.php** (5% par requête)
```php
if (rand(1, 100) <= 5) {
    $backupManager->autoBackup();
}
```

2. **Vérification périodique** côté client (5 min)
```javascript
setInterval(() => {
    this.checkAutoBackup();
}, 5 * 60 * 1000);
```

3. **Intégration dans d'autres APIs** (recommandé)
```php
// À ajouter dans contact.php, admin.js, etc.
$backupManager = new BackupManager();
$backupManager->autoBackup();
```

### Nettoyage automatique

**Critères de suppression :**
- Plus de 50 sauvegardes total
- Sauvegardes > 30 jours
- Rotation intelligente (garde les plus récentes)

## Monitoring et statistiques

### Métriques disponibles
```json
{
    "total_backups": 23,
    "total_size": 1048576,
    "total_size_formatted": "1.0 MB",
    "types": {
        "auto": 18,
        "manual": 5
    },
    "oldest_backup": "2024-12-15_10-00-00_auto",
    "newest_backup": "2025-01-15_14-30-00_manual"
}
```

### Alertes recommandées
- Échec de sauvegarde automatique
- Espace disque insuffisant
- Corruption de sauvegarde
- Trop de sauvegardes manuelles

## Maintenance

### Tâches périodiques

1. **Vérifier l'espace disque**
```bash
du -sh /path/to/backups/
```

2. **Nettoyer manuellement**
```bash
find /path/to/backups/ -name "*.zip" -mtime +30 -delete
```

3. **Vérifier l'intégrité**
```bash
for file in /path/to/backups/*.zip; do
    unzip -t "$file" > /dev/null && echo "OK: $file" || echo "ERREUR: $file"
done
```

### Configuration serveur

**PHP.ini recommandé :**
```ini
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
```

**Extensions requises :**
- `zip` pour la compression
- `json` pour les métadonnées
- `fileinfo` pour la validation

## Restauration d'urgence

### Restauration manuelle

1. **Extraire l'archive**
```bash
unzip 2025-01-15_14-30-00_manual.zip -d /tmp/restore/
```

2. **Copier les fichiers**
```bash
cp /tmp/restore/*.json /path/to/data/
```

3. **Vérifier les permissions**
```bash
chmod 644 /path/to/data/*.json
```

### Restauration via API

```bash
curl -X POST \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=restore&backup_id=2025-01-15_14-30-00_manual&csrf_token=abc123" \
  https://site.com/api/backup.php
```

## Dépannage

### Problèmes courants

1. **"Impossible de créer le dossier de sauvegarde"**
   - Vérifier les permissions (755)
   - Vérifier l'espace disque
   - Contrôler les chemins d'accès

2. **"Extension ZIP non disponible"**
   - Installer `php-zip`
   - Recompiler PHP avec `--enable-zip`

3. **"Sauvegarde introuvable"**
   - Vérifier que le fichier existe
   - Contrôler les permissions de lecture
   - Vérifier la structure des métadonnées

4. **"Authentification requise"**
   - Vérifier la session admin
   - Contrôler les cookies
   - Tester la connexion

### Debug

```php
// Activer les logs détaillés
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tester la création de sauvegarde
$backupManager = new BackupManager();
$result = $backupManager->createBackup('test');
var_dump($result);
```

## Bonnes pratiques

### Développement
- Tester les sauvegardes régulièrement
- Vérifier l'intégrité des archives
- Monitorer l'espace disque
- Documenter les modifications

### Production
- Sauvegardes automatiques activées
- Monitoring des échecs
- Rotation appropriée
- Backups externes recommandés

### Sécurité
- Accès admin uniquement
- Tokens CSRF obligatoires
- Logs d'audit
- Chiffrement des backups (future amélioration)

## Améliorations futures

### Fonctionnalités avancées
- **Sauvegarde incrémentielle**
- **Compression différentielle**
- **Chiffrement des archives**
- **Sauvegarde vers cloud** (S3, Drive)
- **Notifications email** des échecs
- **API webhooks** pour intégration

### Optimisations
- **Compression par niveau**
- **Déduplication des données**
- **Sauvegarde en arrière-plan**
- **Progress bars** pour les grosses sauvegardes

---
*Système de sauvegarde automatique implémenté le 2025-07-15*