# Tests automatisés - ReMmailleuse

## 🎯 **Système de tests complet**

Le système de tests automatisés est maintenant opérationnel avec une couverture complète du code backend et frontend.

## 🏗️ **Architecture des tests**

### **Structure des dossiers**
```
tests/
├── unit/              # Tests unitaires
│   ├── EmailManagerTest.php
│   ├── CSRFProtectionTest.php
│   └── analytics.test.js
├── integration/       # Tests d'intégration
│   └── ContactFormTest.php
├── fixtures/          # Données de test
├── bootstrap.php      # Configuration PHP
├── setup.js          # Configuration Jest
└── run-tests.php     # Exécuteur simple
```

### **Frameworks utilisés**
- **PHP** : PHPUnit + exécuteur simple
- **JavaScript** : Jest + Testing Library
- **E2E** : Puppeteer (optionnel)

## 🧪 **Tests PHP**

### **Tests unitaires disponibles**

#### **EmailManagerTest.php**
- ✅ Initialisation de la classe
- ✅ Envoi d'email de contact
- ✅ Envoi avec fichiers joints
- ✅ Notifications admin
- ✅ Alertes de sécurité
- ✅ Erreurs d'upload
- ✅ Configuration invalide
- ✅ Validation des messages

#### **CSRFProtectionTest.php**
- ✅ Génération de tokens
- ✅ Validation de tokens
- ✅ Tokens expirés
- ✅ Rotation de tokens
- ✅ Résistance aux timing attacks
- ✅ Headers X-CSRF-Token

### **Tests d'intégration**

#### **ContactFormTest.php**
- ✅ Soumission complète du formulaire
- ✅ Upload de fichiers
- ✅ Validation des données
- ✅ Protection CSRF
- ✅ Rate limiting
- ✅ Nettoyage des données
- ✅ Gestion des erreurs

## 🟨 **Tests JavaScript**

### **Tests unitaires disponibles**

#### **analytics.test.js**
- ✅ Initialisation AnalyticsManager
- ✅ Chargement des paramètres
- ✅ Gestion du consentement
- ✅ Tracking d'événements
- ✅ Tracking de pages
- ✅ Tracking de téléchargements
- ✅ Fonctions spécialisées
- ✅ Auto-tracking

## 🚀 **Exécution des tests**

### **Tests PHP**

#### **Méthode 1 : PHPUnit (recommandé)**
```bash
# Installation des dépendances
composer install

# Tous les tests
composer test

# Tests unitaires seulement
composer test:unit

# Tests d'intégration seulement
composer test:integration

# Avec couverture de code
composer test:coverage
```

#### **Méthode 2 : Exécuteur simple**
```bash
# Exécution directe
php tests/run-tests.php

# Ou via npm
npm run test:php
```

### **Tests JavaScript**

```bash
# Installation des dépendances
npm install

# Tous les tests JS
npm run test:js

# Mode watch (développement)
npm run test:watch

# Avec couverture
npm run test:coverage

# Tests E2E
npm run test:e2e
```

### **Tous les tests**
```bash
# Exécuter tous les tests (PHP + JS)
npm run test
```

## 📊 **Couverture de code**

### **Seuils de couverture**
- **Branches** : 80%
- **Fonctions** : 80%
- **Lignes** : 80%
- **Statements** : 80%

### **Rapports générés**
- **HTML** : `coverage/html/index.html`
- **Text** : `coverage/coverage.txt`
- **LCOV** : `coverage/lcov.info`

## 🔧 **Configuration**

### **PHPUnit** (`phpunit.xml`)
```xml
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### **Jest** (`jest.config.js`)
```javascript
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80
    }
  }
};
```

## 🛠️ **Fonctionnalités de test**

### **Mocks et helpers**
- **Fonction `mail()`** : Mockée pour les tests
- **Données de test** : Générées automatiquement
- **Images de test** : Créées à la volée
- **Configuration** : Fichiers de test isolés
- **Nettoyage** : Automatique après chaque test

### **Environnement de test**
- **Variables d'environnement** : `APP_ENV=testing`
- **Répertoires séparés** : `tests/temp/`, `tests/uploads/`
- **Sessions isolées** : Pas d'interférence entre tests
- **Logs séparés** : `tests/temp/test-emails.log`

## 📝 **Écriture de nouveaux tests**

### **Test PHP unitaire**
```php
<?php
use PHPUnit\Framework\TestCase;

class MonNouveauTest extends TestCase
{
    protected function setUp(): void
    {
        // Configuration avant chaque test
    }

    public function testMaFonction()
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = maFonction($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### **Test JavaScript**
```javascript
describe('MonModule', () => {
    beforeEach(() => {
        // Configuration avant chaque test
    });

    test('devrait faire quelque chose', () => {
        // Arrange
        const input = 'test';
        
        // Act
        const result = monModule.maFonction(input);
        
        // Assert
        expect(result).toBe('expected');
    });
});
```

## 🔍 **Debugging des tests**

### **Tests PHP**
```bash
# Afficher les détails
phpunit --verbose

# Arrêter au premier échec
phpunit --stop-on-failure

# Tests spécifiques
phpunit --filter testMaFonction
```

### **Tests JavaScript**
```bash
# Mode debug
npm run test:watch

# Tests spécifiques
npm run test:js -- --testNamePattern="MonModule"

# Avec plus de détails
npm run test:js -- --verbose
```

## 📈 **Intégration continue**

### **Scripts package.json**
```json
{
  "scripts": {
    "test": "npm run test:php && npm run test:js",
    "test:php": "cd tests && php -f run-tests.php",
    "test:js": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage"
  }
}
```

### **Hooks pré-commit**
```bash
# Ajouter au .git/hooks/pre-commit
#!/bin/sh
npm run test
```

## 🎯 **Bonnes pratiques**

### **Nommage des tests**
- **PHP** : `testFonctionnaliteDescription()`
- **JavaScript** : `'devrait faire quelque chose'`

### **Structure AAA**
1. **Arrange** : Préparer les données
2. **Act** : Exécuter la fonction
3. **Assert** : Vérifier le résultat

### **Isolation des tests**
- Chaque test est indépendant
- Nettoyage automatique
- Pas d'état partagé

## 🔧 **Maintenance**

### **Ajout de nouveaux tests**
1. Créer le fichier dans le bon dossier
2. Suivre les conventions de nommage
3. Ajouter au script de build si nécessaire
4. Vérifier la couverture

### **Mise à jour des tests**
- Réviser après chaque modification du code
- Maintenir la couverture au-dessus des seuils
- Documenter les tests complexes

## 📋 **Checklist tests**

### **Avant le déploiement**
- [ ] Tous les tests passent
- [ ] Couverture > 80%
- [ ] Pas de tests ignorés
- [ ] Logs d'erreurs vides

### **Après nouvelle fonctionnalité**
- [ ] Tests unitaires ajoutés
- [ ] Tests d'intégration si nécessaire
- [ ] Documentation mise à jour
- [ ] CI/CD fonctionnel

## 🎉 **Système opérationnel !**

Le système de tests automatisés est maintenant :
- ✅ **Complet** : Unit + Integration + E2E
- ✅ **Performant** : Exécution rapide
- ✅ **Fiable** : Mocks et isolation
- ✅ **Maintenable** : Documentation et bonnes pratiques

**Les tests garantissent la qualité et la fiabilité du code !**

---

*Documentation créée le 15/07/2025*
*Système de tests - Projet ReMmailleuse*