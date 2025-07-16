# 🔧 **Plan de Travail - Corrections Debug ReMmailleuse**

*Créé le : 16 juillet 2025*  
*Status : À démarrer*

## 📋 **Vue d'ensemble**

**Objectif** : Corriger tous les problèmes identifiés lors du debug  
**Estimation totale** : 14-21h de travail  
**Priorité** : Sécurité > Configuration > Performance > Nettoyage

---

## 🔴 **PHASE 1 : SÉCURITÉ CRITIQUE** *(2-3h)*

### 1.1 Correction des mots de passe en dur
- [ ] **Changer le mot de passe admin** dans `api/auth.php:38`
  - [ ] Générer nouveau hash : `password_hash('nouveau_mdp', PASSWORD_DEFAULT)`
  - [ ] Remplacer la constante `ADMIN_PASSWORD_HASH`
  - [ ] Documenter le nouveau mot de passe en sécurité
  - 📚 **Source** : [PHP Password Hashing](https://www.php.net/manual/en/function.password-hash.php)

- [ ] **Sécuriser les clés secrètes**
  - [ ] Clé webcron dans `api/webcron-monitor.php:14`
  - [ ] Clé backup dans `api/production-backup.php:10`
  - [ ] Utiliser des variables d'environnement ou fichier `.env`
  - 📚 **Source** : [PHP Environment Variables](https://www.php.net/manual/en/function.getenv.php)

- [ ] **Supprimer les fichiers sensibles**
  - [ ] Supprimer `api/generate_hash.php`
  - [ ] Supprimer `api/setup_password.php`
  - [ ] Vérifier qu'aucun script de génération reste accessible
  - 📚 **Source** : [OWASP Security by Design](https://owasp.org/www-project-secure-coding-practices-quick-reference-guide/)

### 1.2 Correction des vulnérabilités XSS
- [ ] **Audit des innerHTML** dans `assets/js/main.js`
  - [ ] Ligne 172 : `titleElement.innerHTML = hero.title`
  - [ ] Remplacer par `textContent` ou sanitiser avec DOMPurify
  - [ ] Vérifier tous les usages d'`innerHTML`
  - 📚 **Source** : [DOMPurify GitHub](https://github.com/cure53/DOMPurify)

- [ ] **Sécuriser admin.js**
  - [ ] Lignes 281-307 : Template strings avec données utilisateur
  - [ ] Implémenter la sanitisation HTML
  - [ ] Ajouter validation côté client
  - 📚 **Source** : [OWASP XSS Prevention](https://owasp.org/www-community/xss-filter-evasion-cheatsheet)

---

## 🔴 **PHASE 2 : CONFIGURATION CRITIQUE** *(1-2h)*

### 2.1 Création des ressources PWA manquantes
- [ ] **Icônes PWA**
  - [ ] Créer `assets/images/icons/icon-128.png`
  - [ ] Créer `assets/images/icons/icon-152.png`
  - [ ] Créer `assets/images/icons/icon-384.png`
  - [ ] Optimiser toutes les icônes (compression)
  - 📚 **Source** : [PWA Icon Generator](https://tools.crawlink.com/tools/pwa-icon-generator)

- [ ] **Screenshots PWA**
  - [ ] Créer `assets/images/screenshots/desktop-home.png` (1280x720)
  - [ ] Créer `assets/images/screenshots/mobile-gallery.png` (375x667)
  - [ ] Prendre screenshots réels du site
  - 📚 **Source** : [PWA Screenshots Guide](https://web.dev/add-manifest/#screenshots)

- [ ] **Icônes de raccourcis**
  - [ ] Créer `assets/images/icons/shortcut-contact.png` (96x96)
  - [ ] Créer `assets/images/icons/shortcut-gallery.png` (96x96)
  - [ ] Créer `assets/images/icons/shortcut-services.png` (96x96)
  - 📚 **Source** : [Web App Shortcuts](https://web.dev/app-shortcuts/)

### 2.2 Correction des directives .htaccess
- [ ] **Nettoyer les directives incompatibles**
  - [ ] Supprimer `ServerTokens Prod` (ligne 213)
  - [ ] Supprimer `ServerSignature Off` (ligne 214)
  - [ ] Adapter pour hébergement mutualisé
  - 📚 **Source** : [Apache .htaccess Guide](https://httpd.apache.org/docs/current/howto/htaccess.html)

- [ ] **Optimiser les limites upload**
  - [ ] Remplacer `LimitRequestBody` par configuration PHP
  - [ ] Ajouter `upload_max_filesize` et `post_max_size`
  - 📚 **Source** : [PHP Upload Limits](https://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize)

### 2.3 Ajout de l'image profil manquante
- [ ] **Créer l'image profil**
  - [ ] Ajouter `assets/images/profile/portrait-mme-monod.jpg`
  - [ ] Optimiser la taille (max 500KB)
  - [ ] Format WebP en alternative
  - 📚 **Source** : [WebP Image Format](https://developers.google.com/speed/webp)

---

## 🟡 **PHASE 3 : PERFORMANCE** *(4-6h)*

### 3.1 Refactorisation des gros fichiers JavaScript
- [ ] **Modulariser admin.js (1218 lignes)**
  - [ ] Créer `admin/auth-manager.js`
  - [ ] Créer `admin/content-manager.js`
  - [ ] Créer `admin/ui-manager.js`
  - [ ] Utiliser ES6 modules
  - 📚 **Source** : [ES6 Modules Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules)

- [ ] **Optimiser main.js (934 lignes)**
  - [ ] Séparer `data-manager.js`
  - [ ] Séparer `ui-components.js`
  - [ ] Lazy loading des composants
  - 📚 **Source** : [JavaScript Code Splitting](https://web.dev/reduce-javascript-payloads-with-code-splitting/)

### 3.2 Optimisation du chargement initial
- [ ] **Réduire les requêtes simultanées**
  - [ ] Grouper les 4 fetch() en un seul endpoint
  - [ ] Implémenter le cache HTTP approprié
  - [ ] Ajouter Service Worker pour offline
  - 📚 **Source** : [HTTP Caching Guide](https://web.dev/http-cache/)

- [ ] **Supprimer le code dupliqué**
  - [ ] Centraliser `getDefaultData()` dans un module
  - [ ] Créer un système de configuration centralisé
  - [ ] Factoriser les fonctions utilitaires
  - 📚 **Source** : [DRY Principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself)

### 3.3 Optimisation des assets
- [ ] **Compresser les fichiers CSS/JS**
  - [ ] Utiliser `npm run build` pour minification
  - [ ] Activer Gzip sur le serveur
  - [ ] Implémenter le cache navigateur
  - 📚 **Source** : [Web Performance Optimization](https://web.dev/fast/)

---

## 🟡 **PHASE 4 : NETTOYAGE DU CODE** *(3-4h)*

### 4.1 Suppression des fichiers obsolètes
- [ ] **Supprimer les fichiers de test**
  - [ ] Supprimer `api/test-admin.php`
  - [ ] Supprimer `api/test-email.php`
  - [ ] Supprimer `api/test-contact.php`
  - [ ] Supprimer `api/test-cleanup.php`
  - [ ] Supprimer `api/contact-simple.php` (redondant)

- [ ] **Nettoyer les dépendances**
  - [ ] Supprimer `puppeteer` du package.json
  - [ ] Supprimer `jest-junit` non utilisé
  - [ ] Supprimer `imagemin-cli` redondant
  - 📚 **Source** : [npm-check Tool](https://www.npmjs.com/package/npm-check)

### 4.2 Simplification des classes PHP
- [ ] **Optimiser DatabaseManager.php**
  - [ ] Supprimer le pool de connexions complexe
  - [ ] Garder uniquement les méthodes utilisées
  - [ ] Simplifier pour usage SQLite/fichier
  - 📚 **Source** : [YAGNI Principle](https://en.wikipedia.org/wiki/You_aren%27t_gonna_need_it)

- [ ] **Nettoyer les Manager classes**
  - [ ] BackupManager : supprimer méthodes test
  - [ ] CleanupManager : simplifier la logique
  - [ ] CacheManager : supprimer compression avancée
  - 📚 **Source** : [Clean Code Principles](https://github.com/ryanmcdermott/clean-code-javascript)

### 4.3 Optimisation JavaScript
- [ ] **Supprimer les fonctions inutiles**
  - [ ] FormValidator, ImageHandler non utilisés
  - [ ] Méthodes simulateFormSubmission()
  - [ ] Composants lazy-loader non utilisés
  - 📚 **Source** : [Dead Code Elimination](https://webpack.js.org/guides/tree-shaking/)

---

## 🟡 **PHASE 5 : CORRECTIONS MINEURES** *(3-4h)*

### 5.1 Amélioration de la gestion d'erreurs JavaScript
- [ ] **Vérifier les accès DOM**
  - [ ] Ajouter vérifications null sur querySelector
  - [ ] Implémenter try/catch complets
  - [ ] Ajouter logging des erreurs
  - 📚 **Source** : [JavaScript Error Handling](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Control_flow_and_error_handling)

### 5.2 Optimisation PHP/API
- [ ] **Améliorer les réponses d'erreur**
  - [ ] Remplacer `exit()` par réponses JSON
  - [ ] Standardiser les codes HTTP
  - [ ] Ajouter logging structuré
  - 📚 **Source** : [HTTP Status Codes](https://httpstatuses.com/)

- [ ] **Nettoyer les headers HTTP**
  - [ ] Supprimer redondances
  - [ ] Optimiser CORS
  - [ ] Améliorer CSP
  - 📚 **Source** : [HTTP Headers Security](https://securityheaders.com/)

### 5.3 Optimisation CSS
- [ ] **Supprimer les styles non utilisés**
  - [ ] Utiliser PurgeCSS ou similaire
  - [ ] Nettoyer les variables CSS
  - [ ] Optimiser les media queries
  - 📚 **Source** : [PurgeCSS Documentation](https://purgecss.com/)

---

## 🟢 **PHASE 6 : TESTS ET VALIDATION** *(2-3h)*

### 6.1 Tests de sécurité
- [ ] **Vérifier les corrections XSS**
  - [ ] Tester avec payloads XSS
  - [ ] Vérifier l'authentification
  - [ ] Audit des permissions
  - 📚 **Source** : [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)

### 6.2 Tests de performance
- [ ] **Mesurer les améliorations**
  - [ ] Lighthouse audit
  - [ ] PageSpeed Insights
  - [ ] Temps de chargement
  - 📚 **Source** : [Google PageSpeed Insights](https://pagespeed.web.dev/)

### 6.3 Tests fonctionnels
- [ ] **Vérifier toutes les fonctionnalités**
  - [ ] Formulaire de contact
  - [ ] Interface admin
  - [ ] PWA et offline
  - 📚 **Source** : [Web App Testing Guide](https://web.dev/test/)

---

## 📊 **SUIVI ET MÉTRIQUES**

### Avant corrections
- [ ] **Baseline sécurité** : 7/10
- [ ] **Baseline performance** : Score Lighthouse actuel
- [ ] **Baseline maintenabilité** : Complexité du code

### Après corrections
- [ ] **Sécurité cible** : 9/10
- [ ] **Performance cible** : +25%
- [ ] **Réduction code** : -30%

---

## 🔧 **OUTILS RECOMMANDÉS**

### Développement
- [ ] **VSCode** avec extensions ESLint, Prettier
- [ ] **Chrome DevTools** pour debug
- [ ] **Lighthouse** pour audit
- 📚 **Source** : [VSCode Extensions](https://code.visualstudio.com/docs/editor/extension-marketplace)

### Sécurité
- [ ] **OWASP ZAP** pour tests sécurité
- [ ] **Burp Suite** pour audit web
- [ ] **DOMPurify** pour sanitisation
- 📚 **Source** : [OWASP Tools](https://owasp.org/www-community/Free_for_Open_Source_Application_Security_Tools)

### Performance
- [ ] **Webpack** pour bundling
- [ ] **Terser** pour minification
- [ ] **ImageOptim** pour images
- 📚 **Source** : [Web Performance Tools](https://web.dev/lighthouse-performance/)

---

## 📝 **NOTES**

- Sauvegarder avant chaque phase majeure
- Tester après chaque correction
- Documenter les changements
- Garder les backups des fichiers modifiés

**Bon courage ! 🚀**

---

*Plan de travail généré par Claude Code - Projet ReMmailleuse Debug*