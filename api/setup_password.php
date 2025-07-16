<?php
/**
 * Script de configuration du mot de passe admin
 * Utiliser ce script pour générer un hash sécurisé
 */

// Vérifier que ce script est appelé depuis la ligne de commande ou localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    die('Accès interdit');
}

echo "=== Configuration du mot de passe admin ===\n\n";

// Demander le mot de passe (ou utiliser celui par défaut)
if (php_sapi_name() === 'cli') {
    echo "Entrez le nouveau mot de passe (ou appuyez sur Entrée pour 'remmailleuse2024'): ";
    $password = trim(fgets(STDIN));
} else {
    $password = $_GET['password'] ?? '';
}

if (empty($password)) {
    $password = 'remmailleuse2024';
}

// Générer le hash sécurisé
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mot de passe configuré : " . $password . "\n";
echo "Hash généré : " . $hash . "\n\n";

// Vérifier le hash
if (password_verify($password, $hash)) {
    echo "✅ Vérification réussie\n\n";
} else {
    echo "❌ Erreur de vérification\n\n";
    exit(1);
}

// Générer le code PHP à copier
echo "=== Code à copier dans auth.php ===\n";
echo "define('ADMIN_PASSWORD_HASH', '{$hash}');\n\n";

// Optionnel : modifier automatiquement le fichier auth.php
if (php_sapi_name() === 'cli') {
    echo "Voulez-vous modifier automatiquement auth.php ? (y/N): ";
    $response = trim(fgets(STDIN));
    
    if (strtolower($response) === 'y') {
        $authFile = __DIR__ . '/auth.php';
        $content = file_get_contents($authFile);
        
        // Remplacer la ligne ADMIN_PASSWORD_HASH
        $pattern = '/define\(\'ADMIN_PASSWORD_HASH\', \'[^\']*\'\);/';
        $replacement = "define('ADMIN_PASSWORD_HASH', '{$hash}');";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            file_put_contents($authFile, $newContent);
            echo "✅ Fichier auth.php mis à jour avec succès\n";
        } else {
            echo "❌ Impossible de mettre à jour auth.php\n";
        }
    }
}

echo "\n=== Configuration terminée ===\n";
echo "Vous pouvez maintenant vous connecter avec :\n";
echo "- Nom d'utilisateur : admin\n";
echo "- Mot de passe : {$password}\n";
?>