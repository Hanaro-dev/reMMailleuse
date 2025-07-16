# Authentification Admin - ReMmailleuse

## Vue d'ensemble

Le système d'authentification admin sécurise l'accès à l'interface d'administration (`/admin/`) avec les fonctionnalités suivantes :

- **Connexion sécurisée** avec nom d'utilisateur et mot de passe
- **Protection contre le brute force** avec limitation des tentatives
- **Sessions PHP** avec expiration automatique
- **Rafraîchissement automatique** de la session
- **Déconnexion sécurisée**

## Fichiers impliqués

### 1. `/api/auth.php` - Backend d'authentification
**Fonctionnalités :**
- Gestion des sessions PHP
- Vérification des identifiants
- Protection contre le brute force
- Nettoyage automatique des tentatives anciennes

### 2. `/admin/login.html` - Page de connexion
**Fonctionnalités :**
- Interface de connexion responsive
- Validation côté client
- Gestion des erreurs
- Redirection automatique si déjà connecté

### 3. `/admin/index.html` - Interface admin (modifiée)
**Ajouts :**
- Bouton de déconnexion dans le header
- Vérification d'authentification au chargement

### 4. `/assets/js/admin.js` - Script admin (modifié)
**Ajouts :**
- Vérification d'authentification au démarrage
- Rafraîchissement automatique de session
- Redirection vers login si non authentifié

## Configuration par défaut

### Identifiants par défaut
- **Nom d'utilisateur :** `admin`
- **Mot de passe :** `remmailleuse2024`

⚠️ **IMPORTANT :** Changez ces identifiants avant la mise en production !

### Sécurité
- **Durée de session :** 2 heures
- **Tentatives max :** 5 par IP
- **Verrouillage :** 15 minutes après 5 échecs
- **Nettoyage auto :** 1% de chance par requête

## Modification des identifiants

### Générer un nouveau hash de mot de passe

```php
<?php
// Remplacez "votre_nouveau_mot_de_passe" par votre mot de passe
$nouveauMotDePasse = "votre_nouveau_mot_de_passe";
$hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
echo "Nouveau hash : " . $hash;
?>
```

### Modifier dans `/api/auth.php`

```php
// Ligne 15-17 : Modifier ces constantes
define('ADMIN_USERNAME', 'votre_nom_utilisateur');
define('ADMIN_PASSWORD_HASH', 'votre_hash_généré');
```

## Utilisation

### Accès à l'administration
1. Aller sur `https://votre-domaine.com/admin/`
2. Redirection automatique vers `/admin/login.html`
3. Saisir les identifiants
4. Redirection vers l'interface admin

### Déconnexion
- Cliquer sur le bouton "🚪 Déconnexion" dans le header
- Confirmation si modifications non sauvegardées
- Redirection vers la page de connexion

## Flux d'authentification

```
┌─────────────────┐
│  Accès /admin/  │
└─────────┬───────┘
          │
          v
┌─────────────────┐    Non      ┌─────────────────┐
│ Vérification    │─────────────▶│ Redirection     │
│ session active  │              │ vers login.html │
└─────────┬───────┘              └─────────────────┘
          │ Oui                           │
          v                               │
┌─────────────────┐                      │
│ Interface admin │                      │
│ accessible      │                      │
└─────────────────┘                      │
          │                               │
          v                               │
┌─────────────────┐                      │
│ Rafraîchissement│                      │
│ session (30min) │                      │
└─────────────────┘                      │
                                         │
                                         v
                              ┌─────────────────┐
                              │ Formulaire de   │
                              │ connexion       │
                              └─────────┬───────┘
                                        │
                                        v
                              ┌─────────────────┐
                              │ Validation      │
                              │ identifiants    │
                              └─────────┬───────┘
                                        │ Succès
                                        v
                              ┌─────────────────┐
                              │ Création session│
                              │ + redirection   │
                              └─────────────────┘
```

## Sécurité implémentée

### Protection contre le brute force
- Max 5 tentatives par IP
- Verrouillage de 15 minutes
- Tracking dans `/temp/login_attempts.json`

### Sécurité des sessions
- Régénération d'ID de session après connexion
- Expiration automatique (2h)
- Vérification à chaque requête
- Rafraîchissement périodique

### Validation des données
- Sanitisation des entrées
- Vérification des types de données
- Protection contre les injections

## Surveillance

### Logs disponibles
- Tentatives de connexion dans `/temp/login_attempts.json`
- Erreurs PHP dans les logs serveur
- Sessions actives dans les données PHP

### Monitoring recommandé
- Surveiller les tentatives de connexion
- Alertes sur les verrouillages répétés
- Vérification des sessions actives

## Maintenance

### Nettoyage automatique
- Suppression des anciennes tentatives (24h)
- Nettoyage des sessions expirées
- Probabilité de nettoyage : 1% par requête

### Maintenance manuelle
- Vider `/temp/login_attempts.json` si nécessaire
- Redémarrer les sessions PHP si problème
- Vérifier les permissions du dossier `/temp/`

## Dépannage

### Problèmes courants

**1. Impossible de se connecter**
- Vérifier les identifiants dans `/api/auth.php`
- Contrôler les permissions du dossier `/temp/`
- Vérifier les logs d'erreur PHP

**2. Session expire trop vite**
- Modifier `SESSION_LIFETIME` dans `/api/auth.php`
- Vérifier la configuration PHP session

**3. Erreur "Trop de tentatives"**
- Attendre 15 minutes ou vider `/temp/login_attempts.json`
- Vérifier l'IP dans le fichier de tracking

**4. Redirection en boucle**
- Vérifier les cookies et la configuration session
- Contrôler les headers HTTP
- Tester en navigation privée

## Améliorations possibles

### Sécurité avancée
- Authentification à deux facteurs (2FA)
- Intégration avec un système d'authentification externe
- Chiffrement des données de session
- Audit trail des connexions

### Fonctionnalités
- Gestion multi-utilisateurs
- Rôles et permissions
- Historique des connexions
- Notification d'intrusion

---
*Système d'authentification implémenté le 2025-07-15*