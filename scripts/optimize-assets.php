#!/usr/bin/env php
<?php
/**
 * ===== SCRIPT D'OPTIMISATION DES ASSETS - SITE REMMAILLEUSE =====
 * Script CLI pour optimiser les assets CSS, JS et images
 * 
 * Usage: php optimize-assets.php [--type=css|js|images|all] [--verbose]
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Vérifier que le script est exécuté en CLI
if (php_sapi_name() !== 'cli') {
    die('Ce script doit être exécuté en ligne de commande');
}

// Inclure l'optimiseur
require_once dirname(__DIR__) . '/api/AssetOptimizer.php';

// Couleurs pour la sortie CLI
class CliColors {
    const RESET = "\033[0m";
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const MAGENTA = "\033[0;35m";
    const CYAN = "\033[0;36m";
    const WHITE = "\033[1;37m";
}

// Fonctions utilitaires
function log_info($message) {
    echo CliColors::BLUE . "[INFO]" . CliColors::RESET . " $message\n";
}

function log_success($message) {
    echo CliColors::GREEN . "[SUCCESS]" . CliColors::RESET . " $message\n";
}

function log_warning($message) {
    echo CliColors::YELLOW . "[WARNING]" . CliColors::RESET . " $message\n";
}

function log_error($message) {
    echo CliColors::RED . "[ERROR]" . CliColors::RESET . " $message\n";
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function displayProgress($current, $total, $message = '') {
    $percent = round(($current / $total) * 100, 2);
    $bar = str_repeat('=', intval($percent / 2));
    $spaces = str_repeat(' ', 50 - strlen($bar));
    
    echo "\r[" . $bar . $spaces . "] $percent% $message";
    
    if ($current >= $total) {
        echo "\n";
    }
}

// Parser les arguments
$options = getopt('', ['type:', 'verbose', 'help']);
$type = $options['type'] ?? 'all';
$verbose = isset($options['verbose']);
$help = isset($options['help']);

// Afficher l'aide
if ($help) {
    echo CliColors::WHITE . "=== OPTIMISEUR D'ASSETS - SITE REMMAILLEUSE ===" . CliColors::RESET . "\n\n";
    echo "Usage: php optimize-assets.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --type=TYPE     Type d'assets à optimiser (css, js, images, all) [défaut: all]\n";
    echo "  --verbose       Mode verbeux\n";
    echo "  --help          Afficher cette aide\n\n";
    echo "Exemples:\n";
    echo "  php optimize-assets.php --type=css --verbose\n";
    echo "  php optimize-assets.php --type=all\n";
    echo "  php optimize-assets.php --help\n\n";
    exit(0);
}

// Validation du type
$validTypes = ['css', 'js', 'images', 'all'];
if (!in_array($type, $validTypes)) {
    log_error("Type invalide '$type'. Types valides: " . implode(', ', $validTypes));
    exit(1);
}

// Initialiser l'optimiseur
log_info("Initialisation de l'optimiseur d'assets...");

try {
    $optimizer = new AssetOptimizer([
        'base_dir' => dirname(__DIR__),
        'minification' => true,
        'compression' => true,
        'source_maps' => false
    ]);
    
    log_success("Optimiseur initialisé avec succès");
    
} catch (Exception $e) {
    log_error("Erreur lors de l'initialisation: " . $e->getMessage());
    exit(1);
}

// Fonction pour afficher les résultats d'optimisation
function displayOptimizationResults($results, $assetType) {
    echo "\n" . CliColors::CYAN . "=== RÉSULTATS $assetType ===" . CliColors::RESET . "\n";
    
    $totalOriginal = 0;
    $totalOptimized = 0;
    $fileCount = 0;
    
    foreach ($results as $fileName => $result) {
        if ($fileName === 'bundle') {
            echo CliColors::MAGENTA . "Bundle combiné:" . CliColors::RESET . "\n";
        } else {
            echo CliColors::WHITE . "$fileName:" . CliColors::RESET . "\n";
        }
        
        $originalSize = $result['original_size'] ?? ($result['bundled_size'] ?? 0);
        $optimizedSize = $result['minified_size'] ?? ($result['bundled_size'] ?? $result['optimized_size'] ?? 0);
        
        $totalOriginal += $originalSize;
        $totalOptimized += $optimizedSize;
        $fileCount++;
        
        echo "  Taille originale: " . formatBytes($originalSize) . "\n";
        echo "  Taille optimisée: " . formatBytes($optimizedSize) . "\n";
        echo "  Compression: " . ($result['compression_ratio'] ?? 0) . "%\n";
        echo "  Fichier: " . ($result['output_file'] ?? 'N/A') . "\n";
        
        if (isset($result['gzip_file'])) {
            echo "  Gzip: " . $result['gzip_file'] . "\n";
        }
        
        echo "\n";
    }
    
    // Résumé
    echo CliColors::GREEN . "Résumé $assetType:" . CliColors::RESET . "\n";
    echo "  Fichiers traités: $fileCount\n";
    echo "  Taille originale totale: " . formatBytes($totalOriginal) . "\n";
    echo "  Taille optimisée totale: " . formatBytes($totalOptimized) . "\n";
    echo "  Économie totale: " . formatBytes($totalOriginal - $totalOptimized) . "\n";
    echo "  Compression moyenne: " . round((1 - $totalOptimized / $totalOriginal) * 100, 2) . "%\n";
}

// Exécuter l'optimisation
$startTime = microtime(true);

try {
    echo CliColors::WHITE . "=== OPTIMISATION DES ASSETS ===" . CliColors::RESET . "\n";
    
    switch ($type) {
        case 'css':
            log_info("Optimisation des fichiers CSS...");
            $results = $optimizer->optimizeCSS();
            displayOptimizationResults($results, 'CSS');
            break;
            
        case 'js':
            log_info("Optimisation des fichiers JavaScript...");
            $results = $optimizer->optimizeJS();
            displayOptimizationResults($results, 'JavaScript');
            break;
            
        case 'images':
            log_info("Optimisation des images...");
            $results = $optimizer->optimizeImages();
            displayOptimizationResults($results, 'Images');
            break;
            
        case 'all':
            log_info("Optimisation de tous les assets...");
            
            // CSS
            log_info("Étape 1/3: Optimisation CSS...");
            $cssResults = $optimizer->optimizeCSS();
            if ($verbose) {
                displayOptimizationResults($cssResults, 'CSS');
            }
            
            // JavaScript
            log_info("Étape 2/3: Optimisation JavaScript...");
            $jsResults = $optimizer->optimizeJS();
            if ($verbose) {
                displayOptimizationResults($jsResults, 'JavaScript');
            }
            
            // Images
            log_info("Étape 3/3: Optimisation Images...");
            $imageResults = $optimizer->optimizeImages();
            if ($verbose) {
                displayOptimizationResults($imageResults, 'Images');
            }
            
            // Résumé global
            $allResults = [
                'css' => $cssResults,
                'js' => $jsResults,
                'images' => $imageResults
            ];
            
            echo "\n" . CliColors::CYAN . "=== RÉSUMÉ GLOBAL ===" . CliColors::RESET . "\n";
            
            $grandTotalOriginal = 0;
            $grandTotalOptimized = 0;
            $grandTotalFiles = 0;
            
            foreach ($allResults as $assetType => $results) {
                $typeOriginal = 0;
                $typeOptimized = 0;
                $typeFiles = count($results);
                
                foreach ($results as $result) {
                    $originalSize = $result['original_size'] ?? ($result['bundled_size'] ?? 0);
                    $optimizedSize = $result['minified_size'] ?? ($result['bundled_size'] ?? $result['optimized_size'] ?? 0);
                    
                    $typeOriginal += $originalSize;
                    $typeOptimized += $optimizedSize;
                }
                
                $grandTotalOriginal += $typeOriginal;
                $grandTotalOptimized += $typeOptimized;
                $grandTotalFiles += $typeFiles;
                
                echo CliColors::WHITE . strtoupper($assetType) . ":" . CliColors::RESET . "\n";
                echo "  Fichiers: $typeFiles\n";
                echo "  Économie: " . formatBytes($typeOriginal - $typeOptimized) . "\n";
                echo "  Compression: " . ($typeOriginal > 0 ? round((1 - $typeOptimized / $typeOriginal) * 100, 2) : 0) . "%\n\n";
            }
            
            echo CliColors::GREEN . "TOTAL GÉNÉRAL:" . CliColors::RESET . "\n";
            echo "  Fichiers traités: $grandTotalFiles\n";
            echo "  Taille originale: " . formatBytes($grandTotalOriginal) . "\n";
            echo "  Taille optimisée: " . formatBytes($grandTotalOptimized) . "\n";
            echo "  Économie totale: " . formatBytes($grandTotalOriginal - $grandTotalOptimized) . "\n";
            echo "  Compression moyenne: " . ($grandTotalOriginal > 0 ? round((1 - $grandTotalOptimized / $grandTotalOriginal) * 100, 2) : 0) . "%\n";
            
            break;
    }
    
    // Générer le manifeste
    log_info("Génération du manifeste...");
    $manifest = $optimizer->generateManifest();
    log_success("Manifeste généré: " . count($manifest['files']) . " fichiers");
    
    // Nettoyer le cache
    log_info("Nettoyage du cache...");
    $cleanedFiles = $optimizer->cleanCache();
    if ($cleanedFiles > 0) {
        log_success("Cache nettoyé: $cleanedFiles fichiers supprimés");
    }
    
    // Temps d'exécution
    $executionTime = round(microtime(true) - $startTime, 3);
    log_success("Optimisation terminée en {$executionTime}s");
    
    // Statistiques finales
    if ($verbose) {
        echo "\n" . CliColors::CYAN . "=== STATISTIQUES ===" . CliColors::RESET . "\n";
        $stats = $optimizer->getStats();
        
        echo "CSS: " . $stats['css']['files'] . " fichiers (" . formatBytes($stats['css']['total_size']) . ")\n";
        echo "JS: " . $stats['js']['files'] . " fichiers (" . formatBytes($stats['js']['total_size']) . ")\n";
        echo "Images: " . $stats['images']['files'] . " fichiers (" . formatBytes($stats['images']['total_size']) . ")\n";
        echo "Total: " . formatBytes($stats['total_size']) . "\n";
    }
    
} catch (Exception $e) {
    log_error("Erreur lors de l'optimisation: " . $e->getMessage());
    exit(1);
}

log_success("Optimisation terminée avec succès !");
exit(0);

?>