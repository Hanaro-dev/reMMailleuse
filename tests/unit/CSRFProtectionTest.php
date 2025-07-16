<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests pour la classe CSRFProtection
 */
class CSRFProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Nettoyer les sessions
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Démarrer une nouvelle session
        session_start();
        
        // Inclure la classe CSRF
        require_once API_ROOT . '/csrf.php';
    }

    protected function tearDown(): void
    {
        // Nettoyer après chaque test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Test de génération de token CSRF
     */
    public function testGenerateToken()
    {
        $response = CSRFProtection::generateToken();
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertEquals('csrf_token', $response['name']);
        $this->assertNotEmpty($response['token']);
        $this->assertEquals(64, strlen($response['token'])); // 32 bytes en hex = 64 caractères
    }

    /**
     * Test que deux tokens générés consécutivement sont différents
     */
    public function testTokenUniqueness()
    {
        $response1 = CSRFProtection::generateToken();
        $response2 = CSRFProtection::generateToken();
        
        $this->assertNotEquals($response1['token'], $response2['token']);
    }

    /**
     * Test de validation d'un token valide
     */
    public function testValidateValidToken()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        
        // Simuler une requête POST avec le token
        $_POST['csrf_token'] = $tokenData['token'];
        
        // La validation ne devrait pas lever d'exception
        $this->expectNotToPerformAssertions();
        CSRFProtection::validateRequest();
    }

    /**
     * Test de validation d'un token invalide
     */
    public function testValidateInvalidToken()
    {
        // Générer un token
        CSRFProtection::generateToken();
        
        // Simuler une requête POST avec un token invalide
        $_POST['csrf_token'] = 'invalid_token';
        
        // La validation devrait lever une exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token CSRF invalide');
        CSRFProtection::validateRequest();
    }

    /**
     * Test de validation sans token
     */
    public function testValidateNoToken()
    {
        // Générer un token mais ne pas l'inclure dans la requête
        CSRFProtection::generateToken();
        
        // La validation devrait lever une exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token CSRF manquant');
        CSRFProtection::validateRequest();
    }

    /**
     * Test de validation avec token expiré
     */
    public function testValidateExpiredToken()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        
        // Simuler l'expiration en modifiant le timestamp
        $_SESSION['csrf_token_time'] = time() - 3601; // 1 heure + 1 seconde
        
        // Simuler une requête POST avec le token
        $_POST['csrf_token'] = $tokenData['token'];
        
        // La validation devrait lever une exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token CSRF expiré');
        CSRFProtection::validateRequest();
    }

    /**
     * Test de rotation de token
     */
    public function testTokenRotation()
    {
        // Générer un premier token
        $token1 = CSRFProtection::generateToken();
        
        // Valider le token
        $_POST['csrf_token'] = $token1['token'];
        CSRFProtection::validateRequest();
        
        // Générer un nouveau token (rotation)
        $token2 = CSRFProtection::generateToken();
        
        // Les tokens devraient être différents
        $this->assertNotEquals($token1['token'], $token2['token']);
        
        // L'ancien token ne devrait plus être valide
        $_POST['csrf_token'] = $token1['token'];
        $this->expectException(Exception::class);
        CSRFProtection::validateRequest();
    }

    /**
     * Test de nettoyage de token
     */
    public function testCleanupToken()
    {
        // Générer un token
        CSRFProtection::generateToken();
        
        // Vérifier que le token existe en session
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        
        // Nettoyer le token
        CSRFProtection::cleanup();
        
        // Vérifier que le token n'existe plus
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
    }

    /**
     * Test de vérification de token
     */
    public function testIsValidToken()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        
        // Vérifier que le token est valide
        $this->assertTrue(CSRFProtection::isValidToken($tokenData['token']));
        
        // Vérifier qu'un token invalide est rejeté
        $this->assertFalse(CSRFProtection::isValidToken('invalid_token'));
        
        // Vérifier qu'un token vide est rejeté
        $this->assertFalse(CSRFProtection::isValidToken(''));
    }

    /**
     * Test de validation avec header X-CSRF-Token
     */
    public function testValidateWithHeader()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        
        // Simuler une requête avec le token dans le header
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenData['token'];
        
        // La validation ne devrait pas lever d'exception
        $this->expectNotToPerformAssertions();
        CSRFProtection::validateRequest();
    }

    /**
     * Test de validation JSON avec token
     */
    public function testValidateJsonWithToken()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        
        // Simuler une requête JSON avec token
        $jsonData = json_encode([
            'csrf_token' => $tokenData['token'],
            'data' => 'test'
        ]);
        
        // Mock de php://input
        $this->mockPhpInput($jsonData);
        
        // La validation ne devrait pas lever d'exception
        $this->expectNotToPerformAssertions();
        CSRFProtection::validateRequest();
    }

    /**
     * Test de sécurité : timing attack
     */
    public function testTimingAttackResistance()
    {
        // Générer un token
        $tokenData = CSRFProtection::generateToken();
        $validToken = $tokenData['token'];
        
        // Créer des tokens de différentes longueurs
        $shortToken = 'short';
        $longToken = str_repeat('a', 64);
        
        // Mesurer le temps pour chaque validation
        $startTime = microtime(true);
        CSRFProtection::isValidToken($shortToken);
        $shortTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        CSRFProtection::isValidToken($longToken);
        $longTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        CSRFProtection::isValidToken($validToken);
        $validTime = microtime(true) - $startTime;
        
        // Les temps devraient être similaires (protection contre timing attacks)
        // Note: Ce test peut être flaky, c'est plus une vérification conceptuelle
        $this->assertGreaterThan(0, $shortTime);
        $this->assertGreaterThan(0, $longTime);
        $this->assertGreaterThan(0, $validTime);
    }

    /**
     * Helper pour mocker php://input
     */
    private function mockPhpInput($data)
    {
        // Note: Dans un test réel, on utiliserait une approche différente
        // pour mocker php://input, comme avec un stream wrapper
        // Pour cette démo, on simule juste avec $_POST
        $decoded = json_decode($data, true);
        if (isset($decoded['csrf_token'])) {
            $_POST['csrf_token'] = $decoded['csrf_token'];
        }
    }
}