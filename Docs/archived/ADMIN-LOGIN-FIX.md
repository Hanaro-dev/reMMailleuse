# 🔧 Résolution du problème de connexion Admin

## 🚨 Problème
Lors de l'accès à `/admin`, vous êtes redirigé vers `/login.html` au lieu de `/admin/login.html`.

## ✅ Solutions

### Solution 1 : Accès direct
1. Allez directement à : `http://localhost:8000/admin/login.html`
2. Connectez-vous avec :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `remmailleuse2024`

### Solution 2 : Page de test
1. Accédez à : `http://localhost:8000/admin/test-login.php`
2. Cliquez sur "Forcer la connexion (test)"
3. Puis cliquez sur "Aller à l'admin"

### Solution 3 : Correction permanente (déjà appliquée)
Les fichiers PHP ont été corrigés pour utiliser `/admin/login.html` au lieu de `login.html`.

## 📝 Notes importantes

1. **Credentials par défaut** :
   - Username : `admin`
   - Password : `remmailleuse2024`

2. **Session** :
   - Durée : 2 heures
   - Protection brute force : 5 tentatives max

3. **URLs importantes** :
   - Login : `http://localhost:8000/admin/login.html`
   - Admin : `http://localhost:8000/admin/` ou `http://localhost:8000/admin/index.php`
   - Test : `http://localhost:8000/admin/test-login.php`

## 🛠️ Dépannage supplémentaire

Si le problème persiste :

1. **Vider le cache du navigateur**
   - Ctrl+Shift+R (Windows/Linux)
   - Cmd+Shift+R (Mac)

2. **Vérifier la session PHP**
   - Accédez à `/admin/test-login.php`
   - Vérifiez l'état de la session

3. **Mode navigation privée**
   - Testez dans une fenêtre de navigation privée

## 🚀 Accès rapide

Copiez-collez cette URL dans votre navigateur :
```
http://localhost:8000/admin/login.html
```

Puis utilisez :
- User: `admin`
- Pass: `remmailleuse2024`