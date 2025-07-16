# Configuration Matomo pour ReMmailleuse

## 🎯 **Configuration automatique**

Le système Matomo est maintenant intégré et prêt à l'emploi !

### ⚙️ **Configuration requise**

1. **Modifier `/data/settings.json`** :
```json
{
  "analytics": {
    "matomo": {
      "enabled": true,
      "url": "https://VOTRE-MATOMO-URL.COM/",
      "site_id": "VOTRE-SITE-ID",
      "anonymize_ip": true,
      "cookie_consent": true,
      "track_downloads": true,
      "track_outlinks": true,
      "respect_dnt": true,
      "disable_cookies": false
    }
  }
}
```

2. **Remplacer les valeurs** :
   - `"url"` : URL de votre serveur Matomo (avec `/` final)
   - `"site_id"` : ID de votre site dans Matomo (généralement `"1"`)

### 🔧 **Fonctionnalités incluses**

#### **Tracking automatique**
- ✅ Pages vues
- ✅ Événements personnalisés
- ✅ Téléchargements
- ✅ Liens sortants
- ✅ Soumissions de formulaires
- ✅ Erreurs 404

#### **Respect de la confidentialité**
- ✅ Anonymisation IP
- ✅ Respect Do Not Track
- ✅ Consentement cookies RGPD
- ✅ Option désactivation cookies

#### **Tracking spécialisé**
- ✅ Galerie d'images
- ✅ Formulaires de contact
- ✅ Actions admin
- ✅ Liens de contact

### 📊 **Événements trackés**

```javascript
// Exemples d'événements automatiques
trackEvent('Contact', 'click', 'phone');
trackEvent('Gallery', 'view', 'pulls');
trackEvent('Form', 'submit_success', 'contact-form');
trackEvent('Error', '404', '/page-inexistante');
```

### 🚀 **Utilisation avancée**

#### **Tracking manuel**
```javascript
// Événement personnalisé
window.trackEvent('Category', 'Action', 'Name', 'Value');

// Page vue personnalisée
window.trackPageView('Titre personnalisé');

// Téléchargement
window.trackDownload('https://example.com/file.pdf', 'brochure.pdf');
```

#### **Vérification du statut**
```javascript
// Obtenir des informations sur le tracking
const info = window.analyticsManager.getTrackingInfo();
console.log(info);
// {
//   enabled: true,
//   hasConsent: true,
//   settings: { ... }
// }
```

### 🔒 **Gestion du consentement**

Le système respecte automatiquement :
- Les préférences de cookies
- Le header Do Not Track
- Les lois RGPD

#### **Consentement manuel**
```javascript
// Activer le tracking
window.analyticsManager.enableTracking();

// Désactiver le tracking
window.analyticsManager.disableTracking();
```

### 🛠️ **Configuration avancée**

#### **Options disponibles dans settings.json**
```json
{
  "matomo": {
    "enabled": true,              // Activer/désactiver
    "url": "https://...",         // URL Matomo
    "site_id": "1",              // ID du site
    "anonymize_ip": true,        // Anonymiser les IPs
    "cookie_consent": true,      // Respect consentement
    "track_downloads": true,     // Tracker téléchargements
    "track_outlinks": true,      // Tracker liens sortants
    "respect_dnt": true,         // Respecter Do Not Track
    "disable_cookies": false     // Désactiver les cookies
  }
}
```

### 🔍 **Debugging**

#### **Vérifier le fonctionnement**
1. Ouvrir les outils développeur (F12)
2. Onglet Console
3. Taper : `window.analyticsManager.getTrackingInfo()`

#### **Vérifier les requêtes**
1. Onglet Network
2. Filtrer par "matomo"
3. Vérifier les appels vers votre serveur

### 📈 **Rapports disponibles**

Le système envoie automatiquement :
- **Pages vues** : Navigation des utilisateurs
- **Événements** : Actions spécifiques
- **Téléchargements** : Fichiers téléchargés
- **Liens sortants** : Clics vers sites externes
- **Formulaires** : Soumissions et erreurs
- **Erreurs** : Pages 404 et autres erreurs

### 🎯 **Prochaines étapes**

1. **Configurer votre serveur Matomo**
2. **Modifier les paramètres dans settings.json**
3. **Tester le tracking**
4. **Vérifier les rapports dans Matomo**

---

## 🔧 **Installation terminée !**

Le système Matomo est maintenant opérationnel et respecte toutes les bonnes pratiques :
- ✅ Confidentialité
- ✅ Performance
- ✅ Facilité d'utilisation
- ✅ Conformité RGPD

**Il ne reste plus qu'à configurer votre URL et site ID !**

---

*Configuration créée le 15/07/2025*
*Documentation technique - Projet ReMmailleuse*