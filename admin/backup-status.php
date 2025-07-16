<?php
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/login.html');
    exit();
}

// Récupérer les statistiques des backups
function getBackupStats() {
    $stats = [
        'production' => [
            'total' => 0,
            'total_size' => 0,
            'last_backup' => null,
            'backups' => []
        ],
        'json' => [
            'total' => 0,
            'total_size' => 0,
            'last_backup' => null,
            'backups' => []
        ]
    ];
    
    // Backups production
    $productionDir = dirname(__DIR__) . '/backups/production';
    if (is_dir($productionDir)) {
        $files = glob($productionDir . '/production_*.zip');
        foreach ($files as $file) {
            $stats['production']['backups'][] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file)
            ];
            $stats['production']['total_size'] += filesize($file);
        }
        $stats['production']['total'] = count($files);
        
        if (!empty($stats['production']['backups'])) {
            usort($stats['production']['backups'], function($a, $b) {
                return $b['date'] - $a['date'];
            });
            $stats['production']['last_backup'] = $stats['production']['backups'][0]['date'];
        }
    }
    
    // Backups JSON
    $jsonDir = dirname(__DIR__) . '/backups';
    if (is_dir($jsonDir)) {
        $files = glob($jsonDir . '/*.zip');
        foreach ($files as $file) {
            $stats['json']['backups'][] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file)
            ];
            $stats['json']['total_size'] += filesize($file);
        }
        $stats['json']['total'] = count($files);
        
        if (!empty($stats['json']['backups'])) {
            usort($stats['json']['backups'], function($a, $b) {
                return $b['date'] - $a['date'];
            });
            $stats['json']['last_backup'] = $stats['json']['backups'][0]['date'];
        }
    }
    
    return $stats;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

$stats = getBackupStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut des Sauvegardes - Administration</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&family=Audiowide&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .backup-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .dashboard-title {
            font-family: 'Audiowide', cursive;
            font-size: 2.5rem;
            color: #673ab7;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #673ab7;
        }
        
        .stat-card h3 {
            color: #673ab7;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .backup-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #673ab7;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .backup-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .backup-table th,
        .backup-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .backup-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .backup-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #673ab7;
            color: white;
        }
        
        .btn-primary:hover {
            background: #563098;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: #673ab7;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #673ab7;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        
        .scroll-to-top.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .scroll-to-top:hover {
            background: #563098;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="backup-dashboard">
        <a href="index.php" class="back-link">← Retour à l'administration</a>
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">📊 Statut des Sauvegardes</h1>
            <p>Surveillance et gestion des sauvegardes système</p>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="createBackup('production')">
                🗂️ Créer Backup Production
            </button>
            <button class="btn btn-primary" onclick="createBackup('json')">
                📄 Créer Backup JSON
            </button>
            <button class="btn btn-secondary" onclick="refreshStats()">
                🔄 Actualiser
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🗂️ Backups Production</h3>
                <div class="stat-value"><?php echo $stats['production']['total']; ?></div>
                <div class="stat-label">Total des sauvegardes</div>
            </div>
            
            <div class="stat-card">
                <h3>📄 Backups JSON</h3>
                <div class="stat-value"><?php echo $stats['json']['total']; ?></div>
                <div class="stat-label">Total des sauvegardes</div>
            </div>
            
            <div class="stat-card">
                <h3>💾 Espace Production</h3>
                <div class="stat-value"><?php echo formatBytes($stats['production']['total_size']); ?></div>
                <div class="stat-label">Espace utilisé</div>
            </div>
            
            <div class="stat-card">
                <h3>📊 Espace JSON</h3>
                <div class="stat-value"><?php echo formatBytes($stats['json']['total_size']); ?></div>
                <div class="stat-label">Espace utilisé</div>
            </div>
        </div>
        
        <div class="backup-section">
            <h2 class="section-title">
                🗂️ Sauvegardes Production
                <?php if ($stats['production']['last_backup']): ?>
                    <span class="status-badge status-success">
                        Dernière: <?php echo date('d/m/Y H:i', $stats['production']['last_backup']); ?>
                    </span>
                <?php endif; ?>
            </h2>
            
            <?php if (!empty($stats['production']['backups'])): ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($stats['production']['backups'], 0, 10) as $backup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                <td><?php echo formatBytes($backup['size']); ?></td>
                                <td><?php echo date('d/m/Y H:i', $backup['date']); ?></td>
                                <td>
                                    <span class="status-badge status-success">✓ Complet</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune sauvegarde production trouvée</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="backup-section">
            <h2 class="section-title">
                📄 Sauvegardes JSON
                <?php if ($stats['json']['last_backup']): ?>
                    <span class="status-badge status-success">
                        Dernière: <?php echo date('d/m/Y H:i', $stats['json']['last_backup']); ?>
                    </span>
                <?php endif; ?>
            </h2>
            
            <?php if (!empty($stats['json']['backups'])): ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($stats['json']['backups'], 0, 10) as $backup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                <td><?php echo formatBytes($backup['size']); ?></td>
                                <td><?php echo date('d/m/Y H:i', $backup['date']); ?></td>
                                <td>
                                    <span class="status-badge status-success">✓ Complet</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune sauvegarde JSON trouvée</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bouton scroll to top -->
    <div class="scroll-to-top" id="scrollToTop">
        ↑
    </div>
    
    <script>
        // Scroll to top functionality
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTop');
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });
        
        document.getElementById('scrollToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Fonctions de gestion des backups
        async function createBackup(type) {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '⏳ Création en cours...';
            button.disabled = true;
            
            try {
                let url;
                if (type === 'production') {
                    url = '../api/production-backup.php?key=remmailleuse_production_backup_2025&action=backup';
                } else {
                    url = '../api/backup.php?action=create';
                }
                
                const response = await fetch(url, {
                    method: 'GET'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Sauvegarde créée avec succès!');
                    refreshStats();
                } else {
                    alert('❌ Erreur lors de la création: ' + (data.error || 'Erreur inconnue'));
                }
            } catch (error) {
                alert('❌ Erreur de connexion: ' + error.message);
            } finally {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
        
        function refreshStats() {
            window.location.reload();
        }
        
        // Auto-refresh toutes les 30 secondes
        setInterval(function() {
            // Actualiser silencieusement les stats
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    // Optionnel: mise à jour partielle du contenu
                });
        }, 30000);
    </script>
</body>
</html>