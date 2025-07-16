# Interface d'Administration - ReMmailleuse

## 🚀 Accès à l'Administration

### URLs d'accès

- **Interface principale** : `https://remmailleuse.ch/admin/`
- **Connexion** : `https://remmailleuse.ch/admin/login.html`
- **Monitoring** : `https://remmailleuse.ch/admin/monitoring-dashboard.php`

### 🔐 Authentification

- **Nom d'utilisateur** : `admin`
- **Mot de passe** : `remmailleuse2024` (à changer en production)

## 📁 Structure des Fichiers

```
/admin/
├── index.php              # Interface principale (avec authentification)
├── index.html             # Ancienne interface (sans authentification)
├── login.html             # Page de connexion
├── monitoring-dashboard.php # Tableau de bord monitoring
├── .htaccess              # Configuration Apache
└── README.md              # Ce fichier
```

## 🔧 Fonctionnalités

### Interface Principale (`index.php`)

- **Authentification automatique** : Vérifie la session avant l'accès
- **Gestion de session** : Expiration automatique après 2 heures
- **Logging de sécurité** : Tous les accès sont enregistrés
- **Bouton monitoring** : Accès direct au tableau de bord
- **Redirection automatique** : Redirige vers login si non connecté

### Page de Connexion (`login.html`)

- **Gestion des sessions expirées** : Message d'information
- **Vérification automatique** : Redirige si déjà connecté
- **Sécurité CSRF** : Protection contre les attaques
- **Design responsive** : Optimisé pour tous les appareils

### Monitoring Dashboard (`monitoring-dashboard.php`)

- **Authentification requise** : Accès protégé
- **Données en temps réel** : Actualisation automatique
- **Alertes visuelles** : Indicateurs de statut
- **Historique graphique** : Évolution des métriques

## 🛡️ Sécurité

### Authentification

- **Session PHP** : Gestion sécurisée des sessions
- **Expiration automatique** : 2 heures d'inactivité
- **Vérification périodique** : Contrôle toutes les minutes
- **Logging complet** : Tous les événements sont enregistrés

### Configuration Apache

- **Redirection automatique** : `/admin` → `/admin/index.php`
- **Headers de sécurité** : X-Frame-Options, X-XSS-Protection
- **Protection des fichiers** : Accès restreint aux fichiers sensibles
- **Compression** : Optimisation des performances

## 🔄 Redirections

### Flux d'authentification

1. **Accès à `/admin`** → Redirection vers `/admin/index.php`
2. **Vérification session** → Si non connecté : redirection vers `login.html`
3. **Connexion réussie** → Redirection vers `index.php`
4. **Session expirée** → Redirection vers `login.html?expired=1`

### URLs de redirection

- `https://remmailleuse.ch/admin` → `https://remmailleuse.ch/admin/index.php`
- `https://remmailleuse.ch/admin/` → `https://remmailleuse.ch/admin/index.php`
- Non authentifié → `https://remmailleuse.ch/admin/login.html`

## 🎨 Design

### Thème visuel

- **Police titre** : Audiowide pour "ReMmailleuse"
- **Couleurs** : Gradient violet cohérent
- **Animations** : Apparition progressive des éléments
- **Bouton scroll** : Retour en haut automatique

### Responsive design

- **Mobile first** : Optimisé pour les petits écrans
- **Tablette** : Interface adaptée
- **Desktop** : Expérience complète

## 📊 Monitoring

### Tableau de bord

- **Statut global** : Score de santé du système
- **Métriques système** : Mémoire, disque, CPU
- **Alertes actives** : Problèmes détectés
- **Historique** : Évolution sur 24h

### Alertes automatiques

- **Email** : Notifications automatiques
- **Cooldown** : 15 minutes entre les alertes
- **Niveaux** : Critical, Error, Warning

## 🔧 Maintenance

### Changement de mot de passe

1. Modifier dans `/api/auth.php` :
```php
define('ADMIN_PASSWORD_HASH', '$2y$10$VOTRE_NOUVEAU_HASH');
```

2. Générer le hash :
```php
echo password_hash('votre_nouveau_mdp', PASSWORD_DEFAULT);
```

### Nettoyage des logs

- **Automatique** : Rotation des logs configurée
- **Manuel** : Interface d'administration
- **Archivage** : Compression automatique

## 📝 Logs

### Fichiers de logs

- `/logs/security.log` : Événements de sécurité
- `/logs/api.log` : Appels API
- `/logs/performance.log` : Métriques de performance
- `/logs/email.log` : Envois d'emails

### Événements loggés

- **Connexions** : Réussies et échouées
- **Accès admin** : Toutes les sessions
- **Modifications** : Changements de configuration
- **Erreurs** : Problèmes système

## 🚨 Dépannage

### Problèmes courants

1. **Redirection infinie** : Vérifier la configuration Apache
2. **Session expirée** : Augmenter la durée dans `index.php`
3. **Erreur 500** : Consulter les logs d'erreur PHP
4. **Pas d'accès** : Vérifier les permissions des fichiers

### Support

En cas de problème :
1. Consulter les logs système
2. Vérifier la configuration Apache
3. Tester l'authentification API
4. Vérifier les permissions des dossiers

---

*Interface d'administration sécurisée pour ReMmailleuse*  
*Version 1.0 - Juillet 2025*