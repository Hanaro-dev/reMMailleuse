<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration pour le formulaire de contact
 */
class ContactFormTest extends TestCase
{
    private $originalPost;
    private $originalFiles;
    private $originalServer;

    protected function setUp(): void
    {
        // Sauvegarder les variables globales
        $this->originalPost = $_POST;
        $this->originalFiles = $_FILES;
        $this->originalServer = $_SERVER;
        
        // Nettoyer les répertoires de test
        cleanTestDirectories();
        clearTestEmails();
        
        // Configurer l'environnement de test
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Agent';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // Démarrer la session pour CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Restaurer les variables globales
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;
        $_SERVER = $this->originalServer;
        
        // Nettoyer
        cleanTestDirectories();
        clearTestEmails();
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Test d'envoi complet du formulaire de contact
     */
    public function testCompleteContactFormSubmission()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        // Préparer les données du formulaire
        $formData = [
            'firstname' => 'Marie',
            'lastname' => 'Dupont',
            'email' => 'marie.dupont@example.com',
            'phone' => '0123456789',
            'message' => 'Bonjour, j\'aurais besoin de remailler un pull en cachemire.',
            'csrf_token' => $tokenData['token']
        ];
        
        // Simuler l'envoi via JSON
        $jsonData = json_encode($formData);
        
        // Capturer la sortie
        ob_start();
        
        // Simuler la requête
        $this->simulateJsonRequest($jsonData);
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('message', $response);
        
        // Vérifier que les emails ont été envoyés
        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));
        
        // Vérifier l'email principal
        $mainEmail = $emails[0];
        $this->assertStringContains('marie.dupont@example.com', $mainEmail['to']);
        $this->assertStringContains('Marie', $mainEmail['message']);
        $this->assertStringContains('cachemire', $mainEmail['message']);
    }

    /**
     * Test d'envoi avec fichiers joints
     */
    public function testContactFormWithFileUploads()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        // Créer des images de test
        $testImage1 = createTestImage(TEST_TEMP . '/test-image1.jpg');
        $testImage2 = createTestImage(TEST_TEMP . '/test-image2.png');
        
        // Préparer les données du formulaire
        $_POST = [
            'firstname' => 'Jean',
            'lastname' => 'Martin',
            'email' => 'jean.martin@example.com',
            'phone' => '0987654321',
            'message' => 'Photos de mon pull à réparer.',
            'csrf_token' => $tokenData['token']
        ];
        
        // Simuler l'upload de fichiers
        $_FILES = [
            'images' => [
                'name' => ['test-image1.jpg', 'test-image2.png'],
                'type' => ['image/jpeg', 'image/png'],
                'tmp_name' => [$testImage1, $testImage2],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [filesize($testImage1), filesize($testImage2)]
            ]
        ];
        
        // Capturer la sortie
        ob_start();
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Vérifier que les emails mentionnent les fichiers
        $emails = getTestEmails();
        $mainEmail = $emails[0];
        $this->assertStringContains('PHOTOS JOINTES', $mainEmail['message']);
        $this->assertStringContains('test-image1.jpg', $mainEmail['message']);
        $this->assertStringContains('test-image2.png', $mainEmail['message']);
    }

    /**
     * Test de validation des données
     */
    public function testFormValidation()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        // Données invalides (email manquant)
        $invalidData = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => '', // Email vide
            'message' => 'Test message',
            'csrf_token' => $tokenData['token']
        ];
        
        // Simuler la requête
        $this->simulateJsonRequest(json_encode($invalidData));
        
        // Capturer la sortie
        ob_start();
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse d'erreur
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertContains('Une adresse email valide est obligatoire', $response['errors']);
    }

    /**
     * Test de protection CSRF
     */
    public function testCSRFProtection()
    {
        // Données valides mais sans token CSRF
        $formData = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'message' => 'Test message'
            // Pas de csrf_token
        ];
        
        // Simuler la requête
        $this->simulateJsonRequest(json_encode($formData));
        
        // Capturer la sortie
        ob_start();
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse d'erreur
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContains('CSRF', $response['error']);
    }

    /**
     * Test de rate limiting
     */
    public function testRateLimiting()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        $formData = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'csrf_token' => $tokenData['token']
        ];
        
        // Faire plusieurs requêtes rapidement
        for ($i = 0; $i < 6; $i++) {
            $tokenData = CSRFProtection::generateToken();
            $formData['csrf_token'] = $tokenData['token'];
            
            ob_start();
            $this->simulateJsonRequest(json_encode($formData));
            include API_ROOT . '/contact.php';
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            if ($i < 5) {
                // Les 5 premières requêtes devraient passer
                $this->assertTrue($response['success']);
            } else {
                // La 6ème devrait être bloquée
                $this->assertFalse($response['success']);
                $this->assertStringContains('Trop de tentatives', $response['error']);
            }
        }
    }

    /**
     * Test de nettoyage des données
     */
    public function testDataSanitization()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        // Données avec du contenu potentiellement dangereux
        $formData = [
            'firstname' => '<script>alert("xss")</script>Marie',
            'lastname' => '  Dupont  ',
            'email' => 'marie.dupont@example.com',
            'phone' => '  01 23 45 67 89  ',
            'message' => '<b>Message</b> avec du HTML et des   espaces multiples',
            'csrf_token' => $tokenData['token']
        ];
        
        // Simuler la requête
        $this->simulateJsonRequest(json_encode($formData));
        
        // Capturer la sortie
        ob_start();
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse
        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        
        // Vérifier que les données ont été nettoyées dans l'email
        $emails = getTestEmails();
        $mainEmail = $emails[0];
        
        // Le script ne devrait pas apparaître tel quel
        $this->assertStringNotContains('<script>', $mainEmail['message']);
        $this->assertStringNotContains('alert("xss")', $mainEmail['message']);
        
        // Les espaces devraient être nettoyés
        $this->assertStringContains('Marie', $mainEmail['message']);
        $this->assertStringContains('Dupont', $mainEmail['message']);
        $this->assertStringContains('01 23 45 67 89', $mainEmail['message']);
    }

    /**
     * Test de gestion des erreurs d'upload
     */
    public function testUploadErrorHandling()
    {
        // Générer un token CSRF
        require_once API_ROOT . '/csrf.php';
        $tokenData = CSRFProtection::generateToken();
        
        // Préparer les données du formulaire
        $_POST = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'csrf_token' => $tokenData['token']
        ];
        
        // Simuler une erreur d'upload
        $_FILES = [
            'images' => [
                'name' => ['too-large.jpg'],
                'type' => ['image/jpeg'],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_FORM_SIZE], // Fichier trop volumineux
                'size' => [0]
            ]
        ];
        
        // Capturer la sortie
        ob_start();
        
        // Inclure et exécuter le script de contact
        include API_ROOT . '/contact.php';
        
        $output = ob_get_clean();
        
        // Vérifier la réponse d'erreur
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContains('upload', strtolower($response['error']));
    }

    /**
     * Simule une requête JSON
     */
    private function simulateJsonRequest($jsonData)
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Simuler php://input
        $decoded = json_decode($jsonData, true);
        if ($decoded) {
            $_POST = $decoded;
        }
    }
}