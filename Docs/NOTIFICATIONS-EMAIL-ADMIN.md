# Notifications Email Admin - ReMmailleuse

## 🎯 **Système de notifications automatiques**

Le système de notifications email admin est maintenant opérationnel et vous avertit automatiquement des événements importants.

## ⚙️ **Configuration**

### **Paramètres dans `/data/settings.json`**

```json
{
  "email": {
    "admin": {
      "enabled": true,
      "notify_on_contact": true,
      "notify_on_upload": true,
      "notify_on_errors": true,
      "to": "admin@remmailleuse.ch",
      "from": "notifications@remmailleuse.ch",
      "subject_prefix": "[ADMIN] ",
      "format": "html"
    }
  }
}
```

### **Paramètres disponibles**

- `enabled` : Active/désactive les notifications admin
- `notify_on_contact` : Notification pour nouveaux contacts
- `notify_on_upload` : Notification pour erreurs d'upload
- `notify_on_errors` : Notification pour alertes sécurité
- `to` : Adresse email de l'administrateur
- `from` : Adresse email d'expédition
- `subject_prefix` : Préfixe des sujets d'email
- `format` : Format d'email (`text` ou `html`)

## 📧 **Types de notifications**

### **1. Nouveau contact**
- **Déclencheur** : Formulaire de contact soumis
- **Contenu** : Détails du contact, message, fichiers joints
- **Sujet** : `[ADMIN] Nouvelle demande de contact`

### **2. Erreur d'upload**
- **Déclencheur** : Échec d'upload de fichier
- **Contenu** : Type d'erreur, détails, IP
- **Sujet** : `[ADMIN] Erreur d'upload`

### **3. Alerte sécurité**
- **Déclencheur** : Tentative d'intrusion, rate limiting
- **Contenu** : Type d'alerte, détails, IP
- **Sujet** : `[ADMIN] Alerte sécurité`

## 🔧 **Utilisation technique**

### **Classe EmailManager**

```php
// Initialiser le gestionnaire
$emailManager = new EmailManager();

// Envoyer notification de contact
$emailManager->sendContactEmail($data, $uploadedFiles);

// Envoyer alerte sécurité
$emailManager->sendSecurityAlert('brute_force', 'Tentative de connexion multiple');

// Envoyer notification d'erreur
$emailManager->sendUploadError('Fichier trop volumineux');

// Tester l'envoi d'email
$emailManager->testEmail();
```

### **Intégration automatique**

Le système s'intègre automatiquement avec :
- **Formulaire de contact** : Notification à chaque soumission
- **Système d'upload** : Notification en cas d'erreur
- **Système d'authentification** : Alertes sécurité

## 📊 **Exemple de notification**

### **Nouveau contact**
```
🔔 NOUVELLE DEMANDE DE CONTACT

Client: Marie Dupont
Email: marie.dupont@example.com
Téléphone: 0123456789

Message:
Bonjour, j'aurais besoin de remailler un pull en cachemire...

📎 Fichiers joints: 2
- pull-avant.jpg
- pull-detail.jpg

🕐 Reçu le: 15/07/2025 à 14:30:15
🌐 IP: 192.168.1.100
```

### **Alerte sécurité**
```
🚨 ALERTE SÉCURITÉ

Type: Rate limit atteint
Détails: 5 tentatives en 60 secondes
IP: 192.168.1.50
Date: 15/07/2025 à 14:35:22
```

## 🧪 **Test du système**

### **Script de test disponible**
```bash
# Test via API (localhost uniquement)
curl -X POST http://localhost:8000/api/test-email.php
```

### **Résultat du test**
```json
{
  "success": true,
  "message": "Tous les tests d'email ont réussi",
  "results": {
    "test_email": true,
    "contact_notification": true,
    "security_alert": true,
    "upload_error": true
  }
}
```

## 📝 **Logs**

Les emails sont automatiquement enregistrés dans :
- **Fichier** : `/logs/email.log`
- **Format** : `[DATE] [NIVEAU] IP - MESSAGE`

### **Exemple de log**
```
[2025-07-15 14:30:15] [INFO] IP:192.168.1.100 - Email de contact envoyé avec succès à admin@remmailleuse.ch
[2025-07-15 14:30:16] [INFO] IP:192.168.1.100 - Email de confirmation envoyé à marie.dupont@example.com
[2025-07-15 14:30:17] [INFO] IP:192.168.1.100 - Notification admin envoyée: new_contact
```

## 🔒 **Sécurité**

### **Mesures de protection**
- Validation des adresses email
- Logs de tous les envois
- Rate limiting intégré
- Nettoyage automatique des données

### **Filtres anti-spam**
- Blacklist de domaines temporaires
- Validation DNS des domaines
- Champs honeypot

## 📈 **Performance**

### **Optimisations**
- Envoi asynchrone pour l'utilisateur
- Logs rotatifs automatiques
- Gestion d'erreurs robuste

### **Monitoring**
- Statistiques d'envoi dans les logs
- Alertes en cas d'échec répétés
- Suivi des performances

## 🎛️ **Configuration avancée**

### **Format HTML**
Activez le format HTML pour des emails plus lisibles :

```json
{
  "email": {
    "admin": {
      "format": "html"
    }
  }
}
```

### **Notifications sélectives**
Désactivez certains types de notifications :

```json
{
  "email": {
    "admin": {
      "notify_on_contact": true,
      "notify_on_upload": false,
      "notify_on_errors": true
    }
  }
}
```

## 🔧 **Maintenance**

### **Vérification périodique**
1. Contrôler les logs d'email
2. Tester l'envoi mensuel
3. Vérifier la boîte spam

### **Résolution de problèmes**
- Vérifier la configuration DNS
- Contrôler les permissions de fichiers
- Tester la fonction `mail()` PHP

## 📋 **Checklist de mise en production**

- [ ] Configurer les adresses email dans settings.json
- [ ] Tester l'envoi d'email avec test-email.php
- [ ] Vérifier la réception des notifications
- [ ] Configurer la rotation des logs
- [ ] Mettre en place la surveillance des erreurs

---

## 🎉 **Système opérationnel !**

Le système de notifications email admin est maintenant :
- ✅ **Configuré** et prêt à l'emploi
- ✅ **Sécurisé** avec logs et validation
- ✅ **Performant** avec gestion d'erreurs
- ✅ **Maintenable** avec documentation complète

**Il ne reste plus qu'à configurer vos adresses email !**

---

*Documentation créée le 15/07/2025*
*Système de notifications - Projet ReMmailleuse*