<?php
/**
 * Exécuteur de tests simple pour PHP
 * Alternative légère à PHPUnit pour les tests de base
 */

// Inclure le bootstrap
require_once __DIR__ . '/bootstrap.php';

// Couleurs pour la sortie console
function colorText($text, $color = 'white') {
    $colors = [
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'white' => '37'
    ];
    
    $colorCode = $colors[$color] ?? '37';
    return "\033[{$colorCode}m{$text}\033[0m";
}

function success($text) {
    echo colorText("✓ $text", 'green') . "\n";
}

function error($text) {
    echo colorText("✗ $text", 'red') . "\n";
}

function info($text) {
    echo colorText("ℹ $text", 'blue') . "\n";
}

function warning($text) {
    echo colorText("⚠ $text", 'yellow') . "\n";
}

// Classe de test simple
class SimpleTest {
    private $passed = 0;
    private $failed = 0;
    private $errors = [];
    
    public function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $this->passed++;
            return true;
        } else {
            $this->failed++;
            $this->errors[] = $message ?: "Expected '$expected', got '$actual'";
            return false;
        }
    }
    
    public function assertTrue($condition, $message = '') {
        if ($condition) {
            $this->passed++;
            return true;
        } else {
            $this->failed++;
            $this->errors[] = $message ?: "Expected true, got false";
            return false;
        }
    }
    
    public function assertFalse($condition, $message = '') {
        if (!$condition) {
            $this->passed++;
            return true;
        } else {
            $this->failed++;
            $this->errors[] = $message ?: "Expected false, got true";
            return false;
        }
    }
    
    public function assertNotEmpty($value, $message = '') {
        if (!empty($value)) {
            $this->passed++;
            return true;
        } else {
            $this->failed++;
            $this->errors[] = $message ?: "Expected non-empty value";
            return false;
        }
    }
    
    public function assertStringContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false) {
            $this->passed++;
            return true;
        } else {
            $this->failed++;
            $this->errors[] = $message ?: "String '$haystack' does not contain '$needle'";
            return false;
        }
    }
    
    public function getResults() {
        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => $this->passed + $this->failed,
            'errors' => $this->errors
        ];
    }
}

// Tests pour EmailManager
function testEmailManager() {
    info("Testing EmailManager...");
    $test = new SimpleTest();
    
    try {
        // Test d'initialisation
        $configPath = createTestConfig();
        require_once API_ROOT . '/EmailManager.php';
        $emailManager = new EmailManager($configPath);
        
        $test->assertTrue($emailManager instanceof EmailManager, "EmailManager should be instantiated");
        
        // Test d'envoi d'email de contact
        $contactData = getTestContactData();
        $result = $emailManager->sendContactEmail($contactData);
        $test->assertTrue($result, "Contact email should be sent successfully");
        
        // Vérifier que l'email a été envoyé
        $emails = getTestEmails();
        $test->assertNotEmpty($emails, "Email should be logged");
        
        if (!empty($emails)) {
            $mainEmail = $emails[0];
            $test->assertEquals('test@example.com', $mainEmail['to'], "Email should be sent to correct recipient");
            $test->assertStringContains('Test', $mainEmail['message'], "Email should contain contact data");
        }
        
        // Test de notification admin
        $result = $emailManager->sendAdminNotification('new_contact', $contactData);
        $test->assertTrue($result, "Admin notification should be sent");
        
        // Test d'alerte de sécurité
        $result = $emailManager->sendSecurityAlert('test_alert', 'Test security alert');
        $test->assertTrue($result, "Security alert should be sent");
        
        success("EmailManager tests completed");
        
    } catch (Exception $e) {
        error("EmailManager test failed: " . $e->getMessage());
    }
    
    return $test->getResults();
}

// Tests pour CSRF
function testCSRF() {
    info("Testing CSRF Protection...");
    $test = new SimpleTest();
    
    try {
        // Démarrer une session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        require_once API_ROOT . '/csrf.php';
        
        // Test de génération de token
        $tokenData = CSRFProtection::generateToken();
        $test->assertTrue($tokenData['success'], "Token generation should succeed");
        $test->assertNotEmpty($tokenData['token'], "Token should not be empty");
        $test->assertEquals('csrf_token', $tokenData['name'], "Token name should be correct");
        
        // Test de validation de token valide
        $_POST['csrf_token'] = $tokenData['token'];
        
        try {
            CSRFProtection::validateRequest();
            $test->assertTrue(true, "Valid token should pass validation");
        } catch (Exception $e) {
            $test->assertTrue(false, "Valid token should not throw exception");
        }
        
        // Test de validation de token invalide
        $_POST['csrf_token'] = 'invalid_token';
        
        try {
            CSRFProtection::validateRequest();
            $test->assertTrue(false, "Invalid token should throw exception");
        } catch (Exception $e) {
            $test->assertTrue(true, "Invalid token should throw exception");
        }
        
        success("CSRF tests completed");
        
    } catch (Exception $e) {
        error("CSRF test failed: " . $e->getMessage());
    }
    
    return $test->getResults();
}

// Tests pour ImageUploadManager
function testImageUpload() {
    info("Testing ImageUploadManager...");
    $test = new SimpleTest();
    
    try {
        require_once API_ROOT . '/ImageUploadManager.php';
        
        // Créer une configuration de test
        $config = [
            'upload_dir' => TEST_UPLOADS . '/images/',
            'max_size' => 1024 * 1024, // 1MB
            'max_files' => 3,
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif']
        ];
        
        $uploadManager = new ImageUploadManager($config);
        $test->assertTrue($uploadManager instanceof ImageUploadManager, "ImageUploadManager should be instantiated");
        
        // Test de validation de fichier
        $validFile = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 50000,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK
        ];
        
        $result = $uploadManager->validateFile($validFile);
        $test->assertTrue($result['valid'], "Valid file should pass validation");
        
        // Test de fichier invalide
        $invalidFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 50000,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK
        ];
        
        $result = $uploadManager->validateFile($invalidFile);
        $test->assertFalse($result['valid'], "Invalid file should fail validation");
        
        success("ImageUploadManager tests completed");
        
    } catch (Exception $e) {
        error("ImageUploadManager test failed: " . $e->getMessage());
    }
    
    return $test->getResults();
}

// Tests de base pour les fonctions utilitaires
function testUtilities() {
    info("Testing utility functions...");
    $test = new SimpleTest();
    
    // Test des fonctions d'aide
    $testData = getTestContactData();
    $test->assertNotEmpty($testData, "Test contact data should not be empty");
    $test->assertTrue(isset($testData['email']), "Test data should contain email");
    
    // Test de création de fichier de test
    $testFile = createTestFile(TEST_TEMP . '/test.txt', 'test content');
    $test->assertTrue(file_exists($testFile), "Test file should be created");
    $test->assertEquals('test content', file_get_contents($testFile), "Test file should contain correct content");
    
    // Test de création d'image de test
    $testImage = createTestImage(TEST_TEMP . '/test.jpg');
    $test->assertTrue(file_exists($testImage), "Test image should be created");
    
    success("Utility tests completed");
    
    return $test->getResults();
}

// Exécuter tous les tests
function runAllTests() {
    echo colorText("=== Tests automatisés ReMmailleuse ===", 'cyan') . "\n\n";
    
    $allResults = [];
    
    // Exécuter chaque groupe de tests
    $testGroups = [
        'Utilities' => 'testUtilities',
        'CSRF' => 'testCSRF',
        'EmailManager' => 'testEmailManager',
        'ImageUpload' => 'testImageUpload'
    ];
    
    foreach ($testGroups as $groupName => $testFunction) {
        echo colorText("--- $groupName ---", 'yellow') . "\n";
        $results = $testFunction();
        $allResults[$groupName] = $results;
        
        if ($results['failed'] > 0) {
            foreach ($results['errors'] as $error) {
                error($error);
            }
        }
        
        echo colorText("Passed: {$results['passed']}, Failed: {$results['failed']}", 'white') . "\n\n";
    }
    
    // Résumé global
    $totalPassed = array_sum(array_column($allResults, 'passed'));
    $totalFailed = array_sum(array_column($allResults, 'failed'));
    $totalTests = $totalPassed + $totalFailed;
    
    echo colorText("=== Résumé global ===", 'cyan') . "\n";
    echo colorText("Total tests: $totalTests", 'white') . "\n";
    
    if ($totalFailed === 0) {
        success("Tous les tests sont passés ! 🎉");
        return 0;
    } else {
        error("$totalFailed test(s) échoué(s)");
        success("$totalPassed test(s) réussi(s)");
        return 1;
    }
}

// Exécuter les tests
$exitCode = runAllTests();

// Nettoyage final
cleanTestDirectories();
clearTestEmails();

exit($exitCode);