# 🔍 Diagnostic de Connexion Admin

## 🚨 Problème
Impossible de se connecter à l'interface admin avec `admin` / `remmailleuse2024`.

## 🔧 Outils de diagnostic créés

### 1. **Debug complet**
URL: `http://localhost:8000/debug-auth.php`
- Affiche la configuration PHP
- Teste les identifiants
- Vérifie les logs
- Test AJAX intégré

### 2. **Force login**
URL: `http://localhost:8000/force-login.php`
- Force la connexion en contournant l'authentification
- Utile pour tester si le problème vient de la session

### 3. **Login simplifié**
URL: `http://localhost:8000/admin/simple-login.html`
- Interface de login avec debug intégré
- Tests CSRF, auth, session
- Multiples options de diagnostic

### 4. **Test de session**
URL: `http://localhost:8000/admin/test-login.php`
- Affiche l'état de la session
- Bouton pour forcer la connexion

## 🔍 Étapes de diagnostic

### Étape 1: Vérifier la configuration
1. Allez sur `http://localhost:8000/debug-auth.php`
2. Vérifiez que les constantes sont correctes
3. Testez la validation des identifiants

### Étape 2: Tester la connexion AJAX
1. Sur la page debug, cliquez sur "Test Login"
2. Vérifiez les erreurs dans la console
3. Regardez les logs de sécurité

### Étape 3: Forcer la connexion
1. Allez sur `http://localhost:8000/force-login.php`
2. Cliquez sur "Aller à l'admin"
3. Si ça fonctionne, le problème vient de l'authentification

### Étape 4: Tester le login simplifié
1. Allez sur `http://localhost:8000/admin/simple-login.html`
2. Testez chaque fonction (CSRF, Auth, Session)
3. Utilisez le formulaire de login

## 🔧 Solutions possibles

### Solution 1: Sessions PHP
```bash
# Vérifier que les sessions fonctionnent
# Dans debug-auth.php, vérifier session_status()
```

### Solution 2: Permissions de fichiers
```bash
# Créer le dossier temp si nécessaire
mkdir -p /var/home/hanaro/Projets_Web/ReMmailleuse/temp
chmod 755 /var/home/hanaro/Projets_Web/ReMmailleuse/temp
```

### Solution 3: Contournement temporaire
```php
// Modifier api/auth.php ligne 202
// Temporairement désactiver CSRF
if (true) { // Au lieu de CSRFProtection::verifyToken($csrfToken)
    // Connexion sans CSRF pour debug
}
```

### Solution 4: Rate limiting
```php
// Vérifier si le rate limiting bloque
// Dans debug-auth.php, vérifier login_attempts.json
```

## 🎯 Identifiants confirmés

D'après la documentation et le code :
- **Username**: `admin`
- **Password**: `remmailleuse2024`
- **Hash**: `$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W`

## 📋 Checklist de vérification

- [ ] PHP sessions activées
- [ ] Dossier `/temp/` existe et accessible
- [ ] Cookies autorisés dans le navigateur
- [ ] JavaScript activé
- [ ] CSRF token généré correctement
- [ ] Pas de rate limiting actif
- [ ] Logs de sécurité vérifiés

## 🚀 Accès rapide

1. **Debug**: http://localhost:8000/debug-auth.php
2. **Force**: http://localhost:8000/force-login.php
3. **Simple**: http://localhost:8000/admin/simple-login.html
4. **Test**: http://localhost:8000/admin/test-login.php

## 📞 Procédure de résolution

1. **Commencer par**: `debug-auth.php` pour voir la configuration
2. **Si session OK**: Tester `simple-login.html`
3. **Si échec**: Utiliser `force-login.php` pour contourner
4. **Si succès**: Le problème vient de l'authentification CSRF/rate limiting

---

*Ces outils de diagnostic permettront d'identifier précisément le problème et de le résoudre rapidement.*