<?php
/**
 * Script pour générer un hash de mot de passe
 * À utiliser pour configurer l'authentification admin
 */

// Mot de passe par défaut
$password = 'remmailleuse2024';

// Générer le hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mot de passe : " . $password . "\n";
echo "Hash généré : " . $hash . "\n";

// Vérifier que le hash fonctionne
if (password_verify($password, $hash)) {
    echo "✅ Vérification réussie - Le hash est correct\n";
} else {
    echo "❌ Erreur - Le hash ne correspond pas\n";
}

// Afficher la ligne à copier dans auth.php
echo "\nLigne à copier dans auth.php :\n";
echo "define('ADMIN_PASSWORD_HASH', '{$hash}');\n";
?>