# Protection CSRF - ReMmailleuse

## Vue d'ensemble

Le système de protection CSRF (Cross-Site Request Forgery) protège les formulaires et requêtes AJAX contre les attaques par falsification de requête intersites.

## Fonctionnement

### 1. Génération de token
- Token unique généré côté serveur
- Stocké en session PHP
- Durée de vie : 1 heure
- Rotation automatique

### 2. Validation
- Vérification du token à chaque requête POST
- Comparaison sécurisée avec `hash_equals()`
- Invalidation après expiration

### 3. Intégration automatique
- Ajout automatique aux formulaires HTML
- Inclusion dans les requêtes AJAX
- Gestion transparente pour le développeur

## Fichiers implémentés

### 1. `/api/csrf.php` - Classe de gestion CSRF

**Fonctionnalités principales :**
- `generateToken()` - Génère un nouveau token
- `verifyToken()` - Vérifie la validité d'un token  
- `validateRequest()` - Middleware de validation
- `getHiddenField()` - Génère un champ HTML hidden
- `cleanupExpiredTokens()` - Nettoie les tokens expirés

**Endpoint AJAX :**
```
GET /api/csrf.php?action=get_token
```

### 2. `/assets/js/csrf.js` - Gestionnaire côté client

**Fonctionnalités :**
- Récupération automatique des tokens
- Ajout aux formulaires HTML
- Intégration avec les requêtes fetch()
- Rotation automatique (30 minutes)
- Observer de mutations DOM

**API JavaScript :**
```javascript
// Fonctions utilitaires globales
window.addCSRFToken(data)      // Ajoute le token aux données
window.getCSRFHeaders()        // Retourne les headers avec token
window.csrfManager.isReady()   // Vérifie si le token est prêt
```

### 3. Intégration dans les APIs

**`/api/contact.php` :**
```php
// Validation CSRF ajoutée
CSRFProtection::validateRequest();
```

**`/api/auth.php` :**
```php
// Validation CSRF dans handleLogin()
if (!CSRFProtection::verifyToken($csrfToken)) {
    // Erreur 403
}
```

## Implémentation automatique

### Formulaires HTML
Les tokens sont ajoutés automatiquement :
```html
<form id="contact-form">
    <!-- Champs du formulaire -->
    <input type="hidden" name="csrf_token" value="abc123..." />
</form>
```

### Requêtes AJAX
```javascript
// Requête POST avec token automatique
const response = await fetch('/api/contact.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(window.addCSRFToken({
        name: 'John Doe',
        email: 'john@example.com'
    }))
});
```

### Requêtes avec FormData
```javascript
// Token ajouté automatiquement aux FormData
const formData = new FormData(form);
// Le token est automatiquement ajouté par csrf.js
```

## Sécurité

### Protection contre les attaques
- **Requêtes intersites** : Token imprévisible
- **Rejeu d'attaques** : Expiration temporelle
- **Timing attacks** : Comparaison sécurisée

### Mesures de sécurité
- Token de 32 bytes (256 bits) aléatoire
- Stockage sécurisé en session PHP
- Validation côté serveur obligatoire
- Expiration automatique (1 heure)

## Configuration

### Variables de configuration
```php
// Dans CSRFProtection
private static $tokenName = 'csrf_token';      // Nom du champ
private static $tokenLifetime = 3600;          // Durée de vie (1h)
```

### Rotation automatique
```javascript
// Rotation côté client (30 minutes)
setInterval(() => {
    window.csrfManager.rotate();
}, 30 * 60 * 1000);
```

## Utilisation

### Formulaires HTML standards
```html
<form method="POST" action="/api/contact.php">
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <!-- Token ajouté automatiquement -->
    <button type="submit">Envoyer</button>
</form>
```

### Requêtes AJAX avec JSON
```javascript
const data = window.addCSRFToken({
    name: 'John',
    email: 'john@example.com'
});

fetch('/api/contact.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
});
```

### Requêtes avec headers
```javascript
const headers = window.getCSRFHeaders();
headers['Content-Type'] = 'application/json';

fetch('/api/contact.php', {
    method: 'POST',
    headers: headers,
    body: JSON.stringify(data)
});
```

## Gestion des erreurs

### Côté serveur
```php
// Erreur 403 - Token invalide
{
    "success": false,
    "error": "Token CSRF invalide ou expiré"
}
```

### Côté client
```javascript
// Récupération automatique en cas d'erreur
if (response.status === 403) {
    await window.csrfManager.refreshToken();
    // Réessayer la requête
}
```

## Monitoring

### Logs de sécurité
- Tentatives avec token invalide
- Tokens expirés
- Requêtes sans token

### Métriques
- Nombre de tokens générés
- Taux de validation réussie
- Fréquence des rotations

## Maintenance

### Nettoyage automatique
```php
// Appel automatique lors des requêtes
CSRFProtection::cleanupExpiredTokens();
```

### Rotation manuelle
```javascript
// Forcer la rotation
await window.csrfManager.rotate();
```

## Compatibilité

### Navigateurs supportés
- Chrome/Edge 60+
- Firefox 55+
- Safari 12+
- Mobile browsers

### Fallback
- Graceful degradation si JavaScript désactivé
- Formulaires toujours protégés côté serveur

## Dépannage

### Problèmes courants

**1. Token manquant**
- Vérifier l'inclusion de `csrf.js`
- Contrôler l'initialisation du gestionnaire

**2. Token expiré**
- Augmenter `$tokenLifetime` si nécessaire
- Vérifier la rotation automatique

**3. Erreur 403 persistante**
- Vérifier les cookies de session
- Contrôler la configuration PHP session

**4. Formulaires non protégés**
- Vérifier l'observer de mutations DOM
- Contrôler l'ajout manuel des tokens

## Bonnes pratiques

### Développement
- Tester avec protection CSRF activée
- Vérifier tous les formulaires
- Utiliser les fonctions utilitaires

### Production
- Activer les logs de sécurité
- Surveiller les erreurs 403
- Rotation régulière des tokens

### Sécurité
- Ne jamais désactiver en production
- Surveiller les tentatives d'attaque
- Maintenir les sessions sécurisées

---
*Système CSRF implémenté le 2025-07-15*