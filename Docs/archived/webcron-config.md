# Configuration Webcron pour ReMmailleuse

## URLs de Webcron

### Sauvegarde Production (Quotidienne)
- **URL**: `https://votre-domaine.com/api/production-backup.php?key=remmailleuse_production_backup_2025&action=backup`
- **Fréquence**: Quotidienne à 02:00
- **Timeout**: 5 minutes

### Sauvegarde JSON (Toutes les 6 heures)
- **URL**: `https://votre-domaine.com/api/backup.php?action=auto`
- **Fréquence**: Toutes les 6 heures
- **Timeout**: 2 minutes

### Monitoring Santé (Toutes les 15 minutes)
- **URL**: `https://votre-domaine.com/api/webcron-monitor.php?key=remmailleuse_monitor_2025&action=health`
- **Fréquence**: Toutes les 15 minutes
- **Timeout**: 1 minute

### Nettoyage Logs (Hebdomadaire)
- **URL**: `https://votre-domaine.com/api/webcron-monitor.php?key=remmailleuse_monitor_2025&action=cleanup`
- **Fréquence**: Hebdomadaire le dimanche à 03:00
- **Timeout**: 3 minutes

## Services Webcron Recommandés

### 1. cron-job.org (Gratuit)
- **Avantages**: Fiable, interface simple, notifications email
- **Limites**: 5 tâches max en gratuit
- **Configuration**: 
  - Créer un compte sur cron-job.org
  - Ajouter les URLs avec les fréquences appropriées
  - Configurer les notifications email en cas d'erreur

### 2. EasyCron (Freemium)
- **Avantages**: Plus de fonctionnalités, historique détaillé
- **Limites**: 20 tâches max en gratuit
- **Configuration**:
  - Créer un compte sur easycron.com
  - Configurer les tâches avec les URLs et fréquences

### 3. SetCronJob (Gratuit)
- **Avantages**: Simple et efficace
- **Limites**: Interface basique
- **Configuration**:
  - Créer un compte sur setcronjob.com
  - Ajouter les tâches cron

## Configuration Recommandée

### Priorité Haute
1. **Backup Production** - Quotidien 02:00
2. **Monitoring Santé** - Toutes les 15 min
3. **Backup JSON** - Toutes les 6h

### Priorité Moyenne
4. **Nettoyage Logs** - Hebdomadaire

## Expressions Cron

```bash
# Backup production - Quotidien à 02:00
0 2 * * *

# Backup JSON - Toutes les 6 heures
0 */6 * * *

# Monitoring - Toutes les 15 minutes
*/15 * * * *

# Nettoyage logs - Dimanche à 03:00
0 3 * * 0
```

## Monitoring et Alertes

### Notifications Email
Configurer les notifications email dans le service webcron pour:
- Échecs de sauvegarde
- Erreurs de monitoring
- Problèmes de performance

### Vérification Manuelle
- Vérifier les logs: `/logs/backup.log`
- Vérifier les backups: `/backups/production/`
- Tester les URLs manuellement

## Sécurité

### Clés d'Accès
- **Production Backup**: `remmailleuse_production_backup_2025`
- **Monitoring**: `remmailleuse_monitor_2025`
- **Backup JSON**: Pas de clé (authentification interne)

### Recommandations
1. Changer les clés avant la production
2. Utiliser HTTPS obligatoirement
3. Surveiller les logs d'accès
4. Configurer des alertes de sécurité

## Dépannage

### Backup Échoue
1. Vérifier l'espace disque disponible
2. Vérifier les permissions des dossiers
3. Consulter `/logs/backup.log`
4. Tester manuellement: `curl "URL_BACKUP"`

### Monitoring Inactif
1. Vérifier la connectivité réseau
2. Vérifier les logs d'erreur PHP
3. Tester l'endpoint de santé
4. Vérifier les permissions des logs

### Performance Lente
1. Vérifier la charge serveur
2. Optimiser les exclusions de backup
3. Augmenter les timeouts webcron
4. Surveiller l'utilisation mémoire

## Exemple de Test Manuel

```bash
# Tester le backup production
curl -X GET "https://votre-domaine.com/api/production-backup.php?key=remmailleuse_production_backup_2025&action=backup"

# Tester le monitoring
curl -X GET "https://votre-domaine.com/api/webcron-monitor.php?key=remmailleuse_monitor_2025&action=health"

# Lister les backups
curl -X GET "https://votre-domaine.com/api/production-backup.php?key=remmailleuse_production_backup_2025&action=list"
```

## Stockage et Rétention

### Backups Production
- **Localisation**: `/backups/production/`
- **Rétention**: 10 backups maximum
- **Rotation**: Automatique (plus anciens supprimés)

### Backups JSON
- **Localisation**: `/backups/`
- **Rétention**: 50 backups maximum
- **Rotation**: 30 jours

### Recommandations
1. Sauvegarder périodiquement sur un stockage externe
2. Vérifier l'intégrité des backups
3. Tester la restauration régulièrement
4. Surveiller l'utilisation de l'espace disque