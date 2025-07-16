# 🚀 Démarrage Rapide - Monitoring Webcron

## ⚡ Configuration en 5 minutes

### 1. **Sécuriser la clé secrète**
```bash
# Modifier dans /api/webcron-monitor.php ligne 14
'secret_key' => 'VOTRE_CLE_UNIQUE_COMPLEXE_2025',
```

### 2. **Tester le monitoring**
```bash
# En local
php scripts/test-webcron.php

# Ou via navigateur
https://votre-domaine.com/scripts/test-webcron.php
```

### 3. **Configurer un service webcron**

#### Option A: **EasyCron** (Recommandé)
1. Aller sur https://www.easycron.com
2. Créer un compte gratuit
3. Ajouter une tâche cron:
   - **URL**: `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
   - **Cron**: `*/15 * * * *` (toutes les 15 minutes)
   - **Timeout**: 30 secondes

#### Option B: **Cron-Job.org**
1. Aller sur https://cron-job.org
2. Créer un compte gratuit
3. Créer un cronjob:
   - **URL**: `https://remmailleuse.ch/api/webcron-monitor.php?key=VOTRE_CLE`
   - **Schedule**: Every 10 minutes

### 4. **Vérifier le tableau de bord**
```
https://remmailleuse.ch/admin/monitoring-dashboard.php
```

### 5. **Configurer les alertes email**
Les alertes sont automatiquement envoyées via le système `EmailManager` existant.

## 🔧 Dépannage Rapide

### Erreur 403 - Accès Refusé
```php
// Dans webcron-monitor.php, désactiver temporairement
'enable_ip_check' => false,
```

### Pas d'alertes reçues
1. Tester `test-email.php`
2. Vérifier `settings.json` pour la configuration email
3. Consulter `/logs/email.log`

### Timeout
- Augmenter le timeout du service webcron à 60 secondes
- Vérifier les performances du serveur

## 📊 URLs Importantes

- **Monitoring**: `/api/webcron-monitor.php?key=VOTRE_CLE`
- **Health Check**: `/api/health-check.php`
- **Dashboard**: `/admin/monitoring-dashboard.php`
- **Test**: `/scripts/test-webcron.php`

## 🎯 Fréquences Recommandées

- **Production**: 5-15 minutes
- **Développement**: 30-60 minutes
- **Test**: 1-5 minutes

## 🔔 Services Webcron Gratuits

| Service | Tâches gratuites | Fréquence min | Timeout |
|---------|------------------|---------------|---------|
| EasyCron | 20/mois | 1 minute | 30s |
| Cron-Job.org | Illimité | 1 minute | 30s |
| WebCron.org | 5 | 1 minute | 30s |
| SetCronJob | 5 | 1 minute | 30s |

## 🔒 Sécurité

1. **Clé secrète complexe**: Minimum 20 caractères
2. **HTTPS obligatoire**: Toujours utiliser HTTPS
3. **Logs de sécurité**: Tous les accès sont loggés
4. **IP restreintes**: Liste d'IPs autorisées configurée

---

## 📝 Checklist Finale

- [ ] Clé secrète configurée
- [ ] Service webcron configuré
- [ ] Test manuel réussi
- [ ] Dashboard accessible
- [ ] Alertes email testées
- [ ] Logs de monitoring vérifiés

**🎉 Votre monitoring est maintenant actif !**