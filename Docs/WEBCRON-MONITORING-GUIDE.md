# Guide de Configuration Webcron - Monitoring ReMmailleuse

## 📋 Configuration pour Hébergement Web

### 🔧 Configuration Initiale

1. **Modifier la clé secrète dans `/api/webcron-monitor.php`** :
```php
'secret_key' => 'votre_cle_secrete_unique_2025',
```

2. **URL à configurer dans votre service de webcron** :
```
https://votre-domaine.com/api/webcron-monitor.php?key=votre_cle_secrete_unique_2025
```

### 🌐 Services de Webcron Recommandés

#### 1. **EasyCron** (Recommandé)
- **Site** : https://www.easycron.com
- **Plan gratuit** : 20 tâches/mois
- **Fréquence recommandée** : Toutes les 15 minutes
- **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`

#### 2. **Cron-Job.org**
- **Site** : https://cron-job.org
- **Plan gratuit** : Tâches illimitées
- **Fréquence recommandée** : Toutes les 10 minutes
- **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`

#### 3. **WebCron.org**
- **Site** : https://webcron.org
- **Plan gratuit** : 5 tâches
- **Fréquence recommandée** : Toutes les 30 minutes
- **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`

#### 4. **SetCronJob**
- **Site** : https://www.setcronjob.com
- **Plan gratuit** : 5 tâches
- **Fréquence recommandée** : Toutes les 15 minutes
- **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`

### ⚙️ Configuration par Service

#### EasyCron
1. Créer un compte sur https://www.easycron.com
2. Aller dans "Cron Jobs" > "Create Cron Job"
3. **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
4. **Cron Expression** : `*/15 * * * *` (toutes les 15 minutes)
5. **Timeout** : 30 secondes
6. **Activer** : Notifications par email en cas d'erreur

#### Cron-Job.org
1. Créer un compte sur https://cron-job.org
2. Aller dans "Cronjobs" > "Create cronjob"
3. **Title** : "ReMmailleuse Monitoring"
4. **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
5. **Schedule** : Every 10 minutes
6. **Enabled** : Cocher
7. **Save notifications** : Activer pour les erreurs

#### WebCron.org
1. Créer un compte sur https://webcron.org
2. Aller dans "Add new job"
3. **URL** : `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
4. **Name** : "ReMmailleuse Health Check"
5. **Schedule** : Every 30 minutes
6. **Timeout** : 30 seconds
7. **Enable job** : Cocher

### 🔒 Sécurité

#### 1. **Clé Secrète**
- Utilisez une clé longue et complexe
- Changez-la régulièrement
- Ne la partagez jamais

#### 2. **IPs Autorisées**
Le système inclut une liste d'IPs autorisées pour les principaux services :
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

#### 3. **Désactiver la Vérification d'IP**
Si vous rencontrez des problèmes d'IP, modifiez dans `webcron-monitor.php` :
```php
'enable_ip_check' => false,
```

### 📊 Monitoring et Alertes

#### 1. **Fréquences Recommandées**
- **Production critique** : 5-10 minutes
- **Production normale** : 15-30 minutes
- **Développement** : 1 heure

#### 2. **Alertes Email**
- Configurées automatiquement via `EmailManager`
- Cooldown de 15 minutes entre les alertes
- Envoyées uniquement pour les statuts `critical` et `error`

#### 3. **Logs**
- Tous les événements sont loggés via le système centralisé
- Accès dans `/logs/security.log` et `/logs/performance.log`

### 📁 Fichiers de Status

#### 1. **Status Webcron** : `/temp/webcron_status.json`
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

#### 2. **Dernière Alerte** : `/temp/webcron_last_alert.txt`
Contient le timestamp de la dernière alerte envoyée.

### 🧪 Tests

#### 1. **Test Manuel**
```bash
curl "https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE"
```

#### 2. **Test d'Alerte**
Modifier temporairement le seuil d'alerte dans le code pour tester.

#### 3. **Vérification des Logs**
Consulter `/logs/security.log` pour les accès webcron.

### 🔧 Dépannage

#### 1. **Erreur 403 - Accès Refusé**
- Vérifier la clé secrète
- Vérifier l'IP du service webcron
- Désactiver temporairement `enable_ip_check`

#### 2. **Erreur 500 - Erreur Interne**
- Consulter `/logs/performance.log`
- Vérifier les permissions des dossiers `/temp/` et `/logs/`

#### 3. **Pas d'Alertes Reçues**
- Vérifier la configuration email dans `settings.json`
- Tester avec `test-email.php`
- Vérifier le cooldown des alertes

#### 4. **Timeout**
- Augmenter le timeout dans le service webcron (30-60 secondes)
- Vérifier les performances du serveur

### 📋 Checklist de Mise en Production

- [ ] Clé secrète configurée et sécurisée
- [ ] Service webcron choisi et configuré
- [ ] Fréquence de monitoring définie
- [ ] Alertes email testées
- [ ] Logs de monitoring vérifiés
- [ ] Test d'accès webcron réussi
- [ ] Permissions des dossiers vérifiées
- [ ] Documentation mise à jour avec l'URL finale

### 🔄 Maintenance

#### 1. **Hebdomadaire**
- Vérifier les logs de monitoring
- Contrôler les alertes reçues
- Tester l'accès webcron

#### 2. **Mensuel**
- Nettoyer les anciens fichiers de status
- Vérifier les performances du monitoring
- Revoir les seuils d'alerte

#### 3. **Rotation des Clés**
- Changer la clé secrète tous les 6 mois
- Mettre à jour la configuration webcron
- Tester après changement

---

## 📞 Support

En cas de problème, consulter :
1. Les logs du système (`/logs/`)
2. Le fichier de status (`/temp/webcron_status.json`)
3. Les logs d'erreur PHP de votre hébergeur

---

*Document mis à jour le 15/07/2025*