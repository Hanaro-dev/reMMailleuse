<?php
/**
 * ===== SCRIPT CRON DE NETTOYAGE - SITE REMMAILLEUSE =====
 * Script à exécuter périodiquement pour nettoyer les fichiers temporaires
 * Usage: php cleanup-cron.php [--type=full|quick|smart] [--verbose]
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Configuration
$baseDir = dirname(__DIR__);
$apiDir = $baseDir . '/api';
$logFile = $baseDir . '/logs/cron-cleanup.log';

// Inclure le gestionnaire de nettoyage
require_once $apiDir . '/CleanupManager.php';

// Fonction de logging pour le cron
function logCron($message, $level = 'INFO') {
    global $logFile;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] [CRON] $message\n";
    
    // Créer le dossier logs s'il n'existe pas
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Afficher aussi sur stdout si verbose
    if (in_array('--verbose', $GLOBALS['argv']) || in_array('-v', $GLOBALS['argv'])) {
        echo $logEntry;
    }
}

// Parser les arguments
$cleanupType = 'smart'; // Par défaut
$verbose = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--type=') === 0) {
        $cleanupType = substr($arg, 7);
    } elseif ($arg === '--verbose' || $arg === '-v') {
        $verbose = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php cleanup-cron.php [OPTIONS]\n";
        echo "Options:\n";
        echo "  --type=TYPE     Type de nettoyage (full, quick, smart) [défaut: smart]\n";
        echo "  --verbose, -v   Mode verbeux\n";
        echo "  --help, -h      Afficher cette aide\n";
        echo "\nTypes de nettoyage:\n";
        echo "  full    Nettoyage complet de tous les fichiers temporaires\n";
        echo "  quick   Nettoyage rapide (rate limiting et auth seulement)\n";
        echo "  smart   Nettoyage intelligent selon la charge système\n";
        exit(0);
    }
}

// Validation du type de nettoyage
if (!in_array($cleanupType, ['full', 'quick', 'smart'])) {
    logCron("Type de nettoyage invalide: $cleanupType", 'ERROR');
    exit(1);
}

// Démarrer le nettoyage
logCron("Démarrage du nettoyage automatique (type: $cleanupType)");

try {
    $cleanupManager = new CleanupManager();
    
    // Obtenir les statistiques avant nettoyage
    $statsBefore = $cleanupManager->getCleanupStats();
    $totalBefore = array_sum($statsBefore);
    
    logCron("Fichiers avant nettoyage: $totalBefore");
    
    // Exécuter le nettoyage selon le type
    switch ($cleanupType) {
        case 'full':
            $result = $cleanupManager->runFullCleanup();
            break;
        case 'quick':
            $result = $cleanupManager->runQuickCleanup();
            break;
        case 'smart':
            $result = $cleanupManager->runSmartCleanup();
            break;
    }
    
    if ($result['success']) {
        $cleaned = $result['cleaned'];
        $duration = $result['duration'];
        
        logCron("Nettoyage terminé avec succès: $cleaned fichiers supprimés en {$duration}s");
        
        // Détails si verbose
        if ($verbose && isset($result['details'])) {
            foreach ($result['details'] as $category => $count) {
                if ($count > 0) {
                    logCron("  - $category: $count fichiers");
                }
            }
        }
        
        // Statistiques après nettoyage
        $statsAfter = $cleanupManager->getCleanupStats();
        $totalAfter = array_sum($statsAfter);
        
        logCron("Fichiers après nettoyage: $totalAfter");
        
        // Vérifier l'espace disque
        $freeSpace = disk_free_space($baseDir);
        $totalSpace = disk_total_space($baseDir);
        $usagePercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
        
        logCron("Espace disque utilisé: {$usagePercent}%");
        
        // Alerte si espace faible
        if ($usagePercent > 90) {
            logCron("ALERTE: Espace disque faible ({$usagePercent}%)", 'WARNING');
        }
        
        exit(0);
        
    } else {
        $error = $result['error'] ?? 'Erreur inconnue';
        logCron("Échec du nettoyage: $error", 'ERROR');
        exit(1);
    }
    
} catch (Exception $e) {
    logCron("Erreur fatale: " . $e->getMessage(), 'ERROR');
    exit(1);
}