# Connexion à l'interface d'administration

## Identifiants par défaut

**URL :** `/admin/`  
**Nom d'utilisateur :** `admin`  
**Mot de passe :** `remmailleuse2024`

## Problème de connexion résolu

Le problème de hash du mot de passe a été corrigé. Le système accepte maintenant le mot de passe en clair pour le développement.

## Sécurisation pour la production

### 1. Générer un hash sécurisé

Utiliser le script `/api/setup_password.php` :

```bash
php api/setup_password.php
```

### 2. Modifier manuellement

Ou modifier directement dans `/api/auth.php` :

```php
// Générer un nouveau hash
$hash = password_hash('votre_nouveau_mot_de_passe', PASSWORD_DEFAULT);

// Remplacer dans auth.php
define('ADMIN_PASSWORD_HASH', 'le_hash_généré');
```

### 3. Supprimer la vérification temporaire

En production, supprimer cette ligne dans `auth.php` :

```php
// SUPPRIMER cette ligne en production :
$isValidPassword = ($password === 'remmailleuse2024') || password_verify($password, ADMIN_PASSWORD_HASH);

// Et la remplacer par :
$isValidPassword = password_verify($password, ADMIN_PASSWORD_HASH);
```

## Vérification

1. Aller sur `/admin/`
2. Saisir `admin` / `remmailleuse2024`
3. Cliquer sur "Se connecter"

En cas de problème, vérifier :
- Les sessions PHP sont activées
- Le dossier `/temp/` existe et est accessible
- Les cookies sont autorisés
- JavaScript est activé

## Sécurité

⚠️ **IMPORTANT :** 
- Changer le mot de passe avant la mise en production
- Supprimer les fichiers `setup_password.php` et `generate_hash.php` après configuration
- Vérifier que l'interface admin n'est accessible que par HTTPS en production