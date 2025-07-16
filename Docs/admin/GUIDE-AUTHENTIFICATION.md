# 🔐 **Guide d'authentification Admin - ReMmailleuse**

*Version consolidée - 16 juillet 2025*

## 📋 **Vue d'ensemble**

Système d'authentification sécurisé pour l'interface d'administration avec protection contre le brute force, sessions PHP sécurisées et diagnostics intégrés.

---

## 🚀 **Accès rapide**

### Identifiants par défaut
- **URL** : `http://localhost:8000/admin/`
- **Username** : `admin`
- **Password** : `remmailleuse2024`

### URLs importantes
- **Login** : `/admin/login.html`
- **Admin** : `/admin/index.php`
- **Debug** : `/debug-auth.php`
- **Test** : `/admin/test-login.php`

---

## 🔧 **Architecture du système**

### Fichiers principaux
- **`/api/auth.php`** - Backend d'authentification
- **`/admin/login.html`** - Interface de connexion
- **`/admin/index.php`** - Interface admin protégée
- **`/assets/js/admin.js`** - Scripts côté client

### Fonctionnalités de sécurité
- ✅ **Sessions PHP** avec expiration (2h)
- ✅ **Protection brute force** (5 tentatives max)
- ✅ **Verrouillage temporaire** (15 min)
- ✅ **Rafraîchissement automatique** de session
- ✅ **Nettoyage automatique** des tentatives

---

## 🔑 **Configuration et modification**

### Changer le mot de passe

#### 1. Générer un nouveau hash
```php
<?php
$nouveauMotDePasse = "votre_nouveau_mot_de_passe";
$hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
echo "Nouveau hash : " . $hash;
?>
```

#### 2. Modifier dans `/api/auth.php`
```php
// Lignes 35-38
define('ADMIN_USERNAME', 'votre_nom_utilisateur');
define('ADMIN_PASSWORD_HASH', 'votre_hash_généré');
```

#### 3. Sécurisation production
```php
// Supprimer cette ligne de développement (auth.php:204)
$isValidPassword = ($password === 'remmailleuse2024') || password_verify($password, ADMIN_PASSWORD_HASH);

// Remplacer par :
$isValidPassword = password_verify($password, ADMIN_PASSWORD_HASH);
```

---

## 🛠️ **Diagnostic et résolution de problèmes**

### Outils de diagnostic disponibles

#### 1. **Debug complet** - `/debug-auth.php`
- Configuration PHP complète
- Test des identifiants
- Vérification des logs
- Test AJAX intégré

#### 2. **Force login** - `/force-login.php`
- Contournement temporaire
- Test de session
- Vérification des permissions

#### 3. **Login simplifié** - `/admin/simple-login.html`
- Interface avec debug intégré
- Tests CSRF, auth, session
- Multiple options de diagnostic

#### 4. **Test de session** - `/admin/test-login.php`
- État de la session
- Bouton force connexion
- Vérifications système

### Procédure de résolution

#### **Étape 1 : Diagnostic initial**
1. Accéder à `http://localhost:8000/debug-auth.php`
2. Vérifier la configuration PHP
3. Tester la validation des identifiants

#### **Étape 2 : Test AJAX**
1. Cliquer sur "Test Login" dans debug
2. Vérifier console navigateur
3. Examiner les logs de sécurité

#### **Étape 3 : Contournement temporaire**
1. Utiliser `http://localhost:8000/force-login.php`
2. Tester l'accès admin
3. Identifier la source du problème

#### **Étape 4 : Login simplifié**
1. Utiliser `/admin/simple-login.html`
2. Tester chaque fonction individuellement
3. Utiliser le formulaire de login

---

## 🚨 **Problèmes courants et solutions**

### ❌ **Impossible de se connecter**
```bash
# Vérifier les identifiants
Username: admin
Password: remmailleuse2024

# Vérifier les permissions
chmod 755 /var/home/hanaro/Projets_Web/ReMmailleuse/temp
```

### ❌ **Redirection en boucle**
```php
// Vérifier les sessions PHP
session_start();
var_dump(session_status()); // Doit être PHP_SESSION_ACTIVE

// Vider le cache navigateur
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

### ❌ **Erreur "Trop de tentatives"**
```bash
# Vider le fichier de tentatives
rm /var/home/hanaro/Projets_Web/ReMmailleuse/temp/login_attempts.json
# Ou attendre 15 minutes
```

### ❌ **Session expire trop vite**
```php
// Modifier SESSION_LIFETIME dans auth.php
define('SESSION_LIFETIME', 7200); // 2 heures
```

---

## 🔒 **Sécurité et monitoring**

### Protection implémentée
- **Rate limiting** : 5 tentatives max par IP
- **Verrouillage temporaire** : 15 minutes
- **Tracking des tentatives** : `/temp/login_attempts.json`
- **Régénération session** : Après connexion réussie
- **Expiration automatique** : 2 heures d'inactivité

### Logs et surveillance
- **Tentatives** : `/temp/login_attempts.json`
- **Erreurs PHP** : Logs serveur
- **Sessions actives** : Données PHP temporaires

### Monitoring recommandé
- Surveiller les tentatives répétées
- Alertes sur verrouillages fréquents
- Vérification des sessions actives
- Audit des connexions réussies

---

## 🧹 **Maintenance**

### Nettoyage automatique
- **Tentatives anciennes** : Supprimées après 24h
- **Sessions expirées** : Nettoyage automatique
- **Probabilité** : 1% par requête

### Maintenance manuelle
```bash
# Vider les tentatives
rm /var/home/hanaro/Projets_Web/ReMmailleuse/temp/login_attempts.json

# Vérifier les permissions
ls -la /var/home/hanaro/Projets_Web/ReMmailleuse/temp/

# Redémarrer les sessions si nécessaire
# (redémarrage serveur web)
```

---

## ⚡ **Checklist de vérification**

### Avant diagnostic
- [ ] PHP sessions activées
- [ ] Dossier `/temp/` accessible
- [ ] Cookies autorisés
- [ ] JavaScript activé
- [ ] CSRF token fonctionnel

### Après modification
- [ ] Nouveau hash généré
- [ ] Constantes mises à jour
- [ ] Mode développement désactivé
- [ ] Fichiers de setup supprimés
- [ ] Tests de connexion réussis

### Production
- [ ] Mot de passe changé
- [ ] Hash sécurisé utilisé
- [ ] Fichiers debug supprimés
- [ ] HTTPS activé
- [ ] Monitoring configuré

---

## 🚀 **Améliorations possibles**

### Sécurité avancée
- Authentification à deux facteurs (2FA)
- Intégration LDAP/OAuth
- Audit trail complet
- Notification d'intrusion

### Fonctionnalités
- Gestion multi-utilisateurs
- Rôles et permissions
- Historique des connexions
- Dashboard de sécurité

---

*Guide d'authentification consolidé - Système sécurisé et diagnostic complet*