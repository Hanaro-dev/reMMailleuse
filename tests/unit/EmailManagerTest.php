<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests pour la classe EmailManager
 */
class EmailManagerTest extends TestCase
{
    private $emailManager;
    private $testConfigPath;

    protected function setUp(): void
    {
        // Créer une configuration de test
        $this->testConfigPath = createTestConfig();
        
        // Inclure la classe EmailManager
        require_once API_ROOT . '/EmailManager.php';
        
        // Créer une instance avec la config de test
        $this->emailManager = new EmailManager($this->testConfigPath);
        
        // Nettoyer les emails de test
        clearTestEmails();
    }

    protected function tearDown(): void
    {
        // Nettoyer après chaque test
        clearTestEmails();
        cleanTestDirectories();
    }

    /**
     * Test de l'initialisation de la classe
     */
    public function testInitialization()
    {
        $this->assertInstanceOf(EmailManager::class, $this->emailManager);
    }

    /**
     * Test d'envoi d'email de contact
     */
    public function testSendContactEmail()
    {
        $contactData = getTestContactData();
        $uploadedFiles = [];

        $result = $this->emailManager->sendContactEmail($contactData, $uploadedFiles);

        $this->assertTrue($result);

        // Vérifier que les emails ont été envoyés
        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));

        // Vérifier le contenu du premier email (email principal)
        $mainEmail = $emails[0];
        $this->assertEquals('test@example.com', $mainEmail['to']);
        $this->assertStringContains('[TEST] Nouvelle demande de devis', $mainEmail['subject']);
        $this->assertStringContains($contactData['firstname'], $mainEmail['message']);
        $this->assertStringContains($contactData['email'], $mainEmail['message']);
    }

    /**
     * Test d'envoi d'email de contact avec fichiers joints
     */
    public function testSendContactEmailWithFiles()
    {
        $contactData = getTestContactData();
        $uploadedFiles = [
            [
                'original_name' => 'test-image.jpg',
                'size' => 150000,
                'type' => 'image/jpeg'
            ],
            [
                'original_name' => 'test-document.pdf',
                'size' => 250000,
                'type' => 'application/pdf'
            ]
        ];

        $result = $this->emailManager->sendContactEmail($contactData, $uploadedFiles);

        $this->assertTrue($result);

        $emails = getTestEmails();
        $mainEmail = $emails[0];
        
        // Vérifier que les fichiers sont mentionnés dans l'email
        $this->assertStringContains('PHOTOS JOINTES', $mainEmail['message']);
        $this->assertStringContains('test-image.jpg', $mainEmail['message']);
        $this->assertStringContains('test-document.pdf', $mainEmail['message']);
    }

    /**
     * Test d'envoi de notification admin
     */
    public function testSendAdminNotification()
    {
        $data = getTestContactData();
        $uploadedFiles = [];

        $result = $this->emailManager->sendAdminNotification('new_contact', $data, $uploadedFiles);

        $this->assertTrue($result);

        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));

        $adminEmail = $emails[0];
        $this->assertEquals('admin@test.com', $adminEmail['to']);
        $this->assertStringContains('[ADMIN TEST] Nouvelle demande de contact', $adminEmail['subject']);
        $this->assertStringContains('NOUVELLE DEMANDE DE CONTACT', $adminEmail['message']);
    }

    /**
     * Test d'envoi d'alerte de sécurité
     */
    public function testSendSecurityAlert()
    {
        $result = $this->emailManager->sendSecurityAlert('brute_force', 'Multiple failed login attempts');

        $this->assertTrue($result);

        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));

        $alertEmail = $emails[0];
        $this->assertEquals('admin@test.com', $alertEmail['to']);
        $this->assertStringContains('[ADMIN TEST] Alerte sécurité', $alertEmail['subject']);
        $this->assertStringContains('ALERTE SÉCURITÉ', $alertEmail['message']);
        $this->assertStringContains('brute_force', $alertEmail['message']);
    }

    /**
     * Test d'envoi de notification d'erreur d'upload
     */
    public function testSendUploadError()
    {
        $result = $this->emailManager->sendUploadError('File too large');

        $this->assertTrue($result);

        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));

        $errorEmail = $emails[0];
        $this->assertEquals('admin@test.com', $errorEmail['to']);
        $this->assertStringContains('[ADMIN TEST] Erreur d\'upload', $errorEmail['subject']);
        $this->assertStringContains('ERREUR D\'UPLOAD', $errorEmail['message']);
        $this->assertStringContains('File too large', $errorEmail['message']);
    }

    /**
     * Test d'envoi d'email de test
     */
    public function testSendTestEmail()
    {
        $result = $this->emailManager->testEmail();

        $this->assertTrue($result);

        $emails = getTestEmails();
        $this->assertGreaterThan(0, count($emails));

        $testEmail = $emails[0];
        $this->assertEquals('admin@test.com', $testEmail['to']);
        $this->assertStringContains('Test Email - Remmailleuse', $testEmail['subject']);
        $this->assertStringContains('Test d\'envoi d\'email', $testEmail['message']);
    }

    /**
     * Test avec configuration admin désactivée
     */
    public function testAdminNotificationsDisabled()
    {
        // Créer une config avec admin désactivé
        $disabledConfig = createTestConfig([
            'email' => [
                'admin' => [
                    'enabled' => false
                ]
            ]
        ]);

        $emailManager = new EmailManager($disabledConfig);
        $data = getTestContactData();

        $result = $emailManager->sendAdminNotification('new_contact', $data);

        $this->assertFalse($result);

        $emails = getTestEmails();
        $this->assertEmpty($emails);
    }

    /**
     * Test avec configuration invalide
     */
    public function testInvalidConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fichier de configuration non trouvé');

        new EmailManager('/path/that/does/not/exist.json');
    }

    /**
     * Test de construction de message de contact
     */
    public function testContactMessageContent()
    {
        $contactData = [
            'firstname' => 'Marie',
            'lastname' => 'Dupont',
            'email' => 'marie@example.com',
            'phone' => '0123456789',
            'message' => 'Test message with special chars: éàü'
        ];

        $result = $this->emailManager->sendContactEmail($contactData);
        $this->assertTrue($result);

        $emails = getTestEmails();
        $mainEmail = $emails[0];

        // Vérifier la présence de tous les éléments
        $this->assertStringContains('NOUVELLE DEMANDE DE DEVIS', $mainEmail['message']);
        $this->assertStringContains('Marie', $mainEmail['message']);
        $this->assertStringContains('Dupont', $mainEmail['message']);
        $this->assertStringContains('marie@example.com', $mainEmail['message']);
        $this->assertStringContains('0123456789', $mainEmail['message']);
        $this->assertStringContains('special chars: éàü', $mainEmail['message']);
        $this->assertStringContains('INFORMATIONS TECHNIQUES', $mainEmail['message']);
    }

    /**
     * Test de construction de message de confirmation
     */
    public function testConfirmationMessageContent()
    {
        $contactData = getTestContactData();
        $result = $this->emailManager->sendContactEmail($contactData);
        $this->assertTrue($result);

        $emails = getTestEmails();
        $this->assertGreaterThan(1, count($emails));

        // Le deuxième email devrait être la confirmation
        $confirmEmail = $emails[1];
        $this->assertEquals($contactData['email'], $confirmEmail['to']);
        $this->assertStringContains('Confirmation de votre demande', $confirmEmail['subject']);
        $this->assertStringContains('Bonjour ' . $contactData['firstname'], $confirmEmail['message']);
        $this->assertStringContains('Mme Monod', $confirmEmail['message']);
        $this->assertStringContains('Rappel de votre demande', $confirmEmail['message']);
    }
}