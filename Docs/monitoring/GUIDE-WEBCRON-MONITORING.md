# 🔍 **Guide Webcron & Monitoring - ReMmailleuse**

*Version consolidée - 16 juillet 2025*

## 🚀 **Démarrage rapide (5 minutes)**

### 1. **Sécuriser les clés secrètes**
```php
// Dans /api/webcron-monitor.php ligne 14
'secret_key' => 'VOTRE_CLE_UNIQUE_COMPLEXE_2025',

// Dans /api/production-backup.php ligne 10
'secret_key' => 'VOTRE_CLE_BACKUP_UNIQUE_2025',
```

### 2. **Tester le monitoring**
```bash
# Test manuel
curl "https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE"

# Test via navigateur
https://remmailleuse.ch/scripts/test-webcron.php
```

### 3. **URLs importantes**
- **Monitoring** : `/api/webcron-monitor.php?key=VOTRE_CLE`
- **Health Check** : `/api/health-check.php`
- **Dashboard** : `/admin/monitoring-dashboard.php`
- **Backup** : `/api/production-backup.php?key=VOTRE_CLE&action=backup`

---

## 🌐 **Services Webcron recommandés**

### 1. **EasyCron** (Recommandé)
- **Site** : https://www.easycron.com
- **Plan gratuit** : 20 tâches/mois
- **Configuration** :
  - **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
  - **Cron** : `*/15 * * * *` (toutes les 15 minutes)
  - **Timeout** : 30 secondes

### 2. **Cron-Job.org**
- **Site** : https://cron-job.org
- **Plan gratuit** : Tâches illimitées
- **Configuration** :
  - **Title** : "ReMmailleuse Monitoring"
  - **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
  - **Schedule** : Every 10 minutes

### 3. **WebCron.org**
- **Site** : https://webcron.org
- **Plan gratuit** : 5 tâches
- **Configuration** :
  - **Name** : "ReMmailleuse Health Check"
  - **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
  - **Schedule** : Every 30 minutes

### 4. **SetCronJob**
- **Site** : https://www.setcronjob.com
- **Plan gratuit** : 5 tâches
- **Configuration** :
  - **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
  - **Schedule** : Every 15 minutes

---

## 📋 **Configuration des tâches**

### **Priorité HAUTE**

#### 1. **Monitoring Santé** (Toutes les 15 minutes)
```bash
# URL: https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE&action=health
# Cron: */15 * * * *
# Timeout: 1 minute
```

#### 2. **Backup Production** (Quotidien à 02:00)
```bash
# URL: https://remmailleuse.ch/api/production-backup.php?key=VOTRE_CLE&action=backup
# Cron: 0 2 * * *
# Timeout: 5 minutes
```

#### 3. **Backup JSON** (Toutes les 6 heures)
```bash
# URL: https://remmailleuse.ch/api/backup.php?action=auto
# Cron: 0 */6 * * *
# Timeout: 2 minutes
```

### **Priorité MOYENNE**

#### 4. **Nettoyage Logs** (Hebdomadaire dimanche 03:00)
```bash
# URL: https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE&action=cleanup
# Cron: 0 3 * * 0
# Timeout: 3 minutes
```

---

## 🔒 **Sécurité**

### **Clés secrètes**
- **Monitoring** : `remmailleuse_monitor_2025` (à changer)
- **Backup** : `remmailleuse_production_backup_2025` (à changer)
- **Critères** : Minimum 20 caractères, complexe, unique

### **IPs autorisées**
Le système inclut une liste d'IPs pour les principaux services :
```php
'allowed_ips' => [
    '46.4.84.',     // EasyCron
    '52.',          // AWS
    '54.',          // AWS
    '34.',          // Google Cloud
    '104.',         // Cloudflare
    '162.',         // Cloudflare
    '172.',         // Cloudflare
    '127.0.0.1',    // Local testing
]
```

### **Désactiver la vérification IP** (si nécessaire)
```php
// Dans webcron-monitor.php
'enable_ip_check' => false,
```

---

## 📊 **Monitoring et alertes**

### **Fréquences recommandées**
- **Production critique** : 5-10 minutes
- **Production normale** : 15-30 minutes
- **Développement** : 1 heure

### **Alertes email**
- Configuration automatique via `EmailManager`
- Cooldown de 15 minutes entre alertes
- Envoi uniquement pour statuts `critical` et `error`

### **Fichiers de status**

#### `/temp/webcron_status.json`
```json
{
    "last_check": {
        "timestamp": 1642248000,
        "status": "healthy",
        "health_score": 95,
        "alerts": []
    },
    "history": [...],
    "updated": "2025-01-15 10:30:00"
}
```

#### `/temp/webcron_last_alert.txt`
Contient le timestamp de la dernière alerte envoyée.

---

## 🛠️ **Dépannage**

### **Erreur 403 - Accès refusé**
```php
// Solutions possibles :
1. Vérifier la clé secrète
2. Vérifier l'IP du service webcron
3. Désactiver temporairement 'enable_ip_check' => false
```

### **Erreur 500 - Erreur interne**
```bash
# Vérifications :
1. Consulter /logs/performance.log
2. Vérifier permissions /temp/ et /logs/
3. Tester avec curl manuellement
```

### **Pas d'alertes reçues**
```bash
# Diagnostic :
1. Vérifier configuration email dans settings.json
2. Tester avec test-email.php
3. Vérifier le cooldown des alertes
4. Consulter /logs/email.log
```

### **Timeout**
```bash
# Solutions :
1. Augmenter timeout service webcron (30-60s)
2. Vérifier performances serveur
3. Optimiser les scripts de monitoring
```

---

## 📁 **Stockage et rétention**

### **Backups Production**
- **Localisation** : `/backups/production/`
- **Rétention** : 10 backups maximum
- **Rotation** : Automatique (plus anciens supprimés)

### **Backups JSON**
- **Localisation** : `/backups/`
- **Rétention** : 50 backups maximum
- **Rotation** : 30 jours

### **Logs**
- **Localisation** : `/logs/`
- **Types** : `security.log`, `performance.log`, `backup.log`
- **Rotation** : Automatique avec compression

---

## 🧪 **Tests**

### **Test manuel complet**
```bash
# Test monitoring
curl "https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE"

# Test backup production
curl "https://remmailleuse.ch/api/production-backup.php?key=VOTRE_CLE&action=backup"

# Test health check
curl "https://remmailleuse.ch/api/health-check.php"

# Lister les backups
curl "https://remmailleuse.ch/api/production-backup.php?key=VOTRE_CLE&action=list"
```

### **Test via navigateur**
```bash
# Script de test complet
https://remmailleuse.ch/scripts/test-webcron.php

# Dashboard de monitoring
https://remmailleuse.ch/admin/monitoring-dashboard.php
```

---

## 📋 **Checklist de mise en production**

### **Configuration**
- [ ] Clés secrètes changées et sécurisées
- [ ] Service webcron choisi et configuré
- [ ] Fréquences de monitoring définies
- [ ] Timeout appropriés configurés

### **Sécurité**
- [ ] HTTPS obligatoire activé
- [ ] IPs autorisées configurées
- [ ] Logs de sécurité activés
- [ ] Alertes email testées

### **Tests**
- [ ] Test d'accès webcron réussi
- [ ] Backup production testé
- [ ] Monitoring santé testé
- [ ] Dashboard accessible

### **Maintenance**
- [ ] Permissions dossiers vérifiées
- [ ] Rotation des logs configurée
- [ ] Rétention des backups définie
- [ ] Documentation mise à jour

---

## 🔄 **Maintenance**

### **Hebdomadaire**
- Vérifier les logs de monitoring
- Contrôler les alertes reçues
- Tester l'accès webcron

### **Mensuel**
- Nettoyer les anciens fichiers de status
- Vérifier les performances du monitoring
- Revoir les seuils d'alerte

### **Semestriel**
- Rotation des clés secrètes
- Mise à jour configuration webcron
- Audit complet de sécurité

---

## 📞 **Support et ressources**

### **Logs à consulter**
- `/logs/security.log` - Accès et sécurité
- `/logs/performance.log` - Performance système
- `/logs/backup.log` - Sauvegardes
- `/logs/email.log` - Alertes email

### **Fichiers de status**
- `/temp/webcron_status.json` - État monitoring
- `/temp/webcron_last_alert.txt` - Dernière alerte
- `/backups/production/` - Backups système

---

**🎉 Votre système de monitoring est maintenant opérationnel !**

*Guide consolidé - Monitoring complet et sécurisé*