<?php
/**
 * Test admin login sans CSRF
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupération des données
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if ($action === 'login') {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    // Vérification des identifiants
    if ($username === 'admin' && password_verify($password, '$2y$12$NowgL/kW.kp1SS.dXfMwU.tn49XYsyt2HAnOpZuS/aYYwaK/0/HHm')) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'redirect' => '/admin/index.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Identifiants incorrects'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Action non valide'
    ]);
}
?>