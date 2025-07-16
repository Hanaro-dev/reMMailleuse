<?php
/**
 * ===== TABLEAU DE BORD MONITORING - SITE REMMAILLEUSE =====
 * Interface web pour visualiser le statut du monitoring
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

// Vérifier l'authentification admin
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/login.html');
    exit();
}

// Charger les données de monitoring
function loadMonitoringData() {
    $data = [
        'webcron_status' => null,
        'health_check' => null,
        'logs_summary' => null,
        'last_updated' => null
    ];
    
    // Charger le statut webcron
    $webcronFile = '../temp/webcron_status.json';
    if (file_exists($webcronFile)) {
        $data['webcron_status'] = json_decode(file_get_contents($webcronFile), true);
        $data['last_updated'] = filemtime($webcronFile);
    }
    
    // Exécuter un health check rapide
    try {
        ob_start();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        include '../api/health-check.php';
        $output = ob_get_clean();
        $data['health_check'] = json_decode($output, true);
    } catch (Exception $e) {
        $data['health_check'] = ['error' => $e->getMessage()];
    }
    
    return $data;
}

$monitoringData = loadMonitoringData();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard - ReMmailleuse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Audiowide&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/monitoring-dashboard.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: #673ab7;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-family: 'Audiowide', cursive;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .nav-links {
            margin-top: 0.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            opacity: 0.8;
        }
        
        .nav-links a:hover {
            opacity: 1;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-bottom: 1rem;
            color: #673ab7;
            font-size: 1.1rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-healthy { background: #4caf50; }
        .status-warning { background: #ff9800; }
        .status-critical { background: #f44336; }
        .status-error { background: #e91e63; }
        .status-unknown { background: #9e9e9e; }
        
        .metric {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .metric:last-child {
            border-bottom: none;
        }
        
        .metric-value {
            font-weight: bold;
        }
        
        .health-score {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin: 1rem 0;
        }
        
        .health-score.good { color: #4caf50; }
        .health-score.warning { color: #ff9800; }
        .health-score.critical { color: #f44336; }
        
        .alerts {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .alerts h4 {
            color: #856404;
            margin-bottom: 0.5rem;
        }
        
        .alert-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #ffeaa7;
        }
        
        .alert-item:last-child {
            border-bottom: none;
        }
        
        .alert-level {
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 0.5rem;
        }
        
        .alert-critical { color: #f44336; }
        .alert-warning { color: #ff9800; }
        .alert-error { color: #e91e63; }
        
        .history-chart {
            height: 200px;
            background: #f9f9f9;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: end;
            justify-content: space-between;
        }
        
        .history-bar {
            background: #673ab7;
            width: 8px;
            min-height: 20px;
            border-radius: 2px;
            margin: 0 1px;
        }
        
        .refresh-btn {
            background: #673ab7;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .refresh-btn:hover {
            background: #5e35b1;
        }
        
        .timestamp {
            color: #666;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #673ab7;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(103, 58, 183, 0.4);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            background: #5e35b1;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(103, 58, 183, 0.5);
        }
        
        .scroll-to-top:active {
            transform: translateY(0);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .header h1 {
                font-size: 1.4rem;
                letter-spacing: 1px;
            }
            
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Monitoring Dashboard - <span style="color: #e1bee7;">re</span><span style="color: #f3e5f5;">M</span><span style="color: #e1bee7;">mailleuse</span></h1>
        <div class="nav-links">
            <a href="dashboard.php">← Retour au Dashboard</a>
            <a href="#" onclick="location.reload()">🔄 Actualiser</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($monitoringData['last_updated']): ?>
            <div class="timestamp">
                Dernière mise à jour: <?php echo date('d/m/Y à H:i:s', $monitoringData['last_updated']); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <!-- Statut Global -->
            <div class="card">
                <h3>🏥 Statut Global</h3>
                <?php if ($monitoringData['health_check']): ?>
                    <?php 
                    $status = $monitoringData['health_check']['status'] ?? 'unknown';
                    $healthScore = 0;
                    
                    if (isset($monitoringData['webcron_status']['last_check']['metrics']['health_score'])) {
                        $healthScore = $monitoringData['webcron_status']['last_check']['metrics']['health_score'];
                    }
                    
                    $scoreClass = $healthScore >= 80 ? 'good' : ($healthScore >= 60 ? 'warning' : 'critical');
                    ?>
                    
                    <div class="metric">
                        <span>Status:</span>
                        <span>
                            <span class="status-indicator status-<?php echo $status; ?>"></span>
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    
                    <div class="health-score <?php echo $scoreClass; ?>">
                        <?php echo $healthScore; ?>/100
                    </div>
                    
                    <div class="metric">
                        <span>Temps d'exécution:</span>
                        <span class="metric-value"><?php echo $monitoringData['health_check']['execution_time_ms'] ?? 0; ?>ms</span>
                    </div>
                    
                    <div class="metric">
                        <span>Dernière vérification:</span>
                        <span class="metric-value"><?php echo $monitoringData['health_check']['timestamp'] ?? 'Inconnue'; ?></span>
                    </div>
                <?php else: ?>
                    <div class="error">Impossible de charger les données de health check</div>
                <?php endif; ?>
            </div>
            
            <!-- Webcron Status -->
            <div class="card">
                <h3>🌐 Statut Webcron</h3>
                <?php if ($monitoringData['webcron_status']): ?>
                    <?php $webcronStatus = $monitoringData['webcron_status']['last_check']; ?>
                    
                    <div class="metric">
                        <span>Dernière exécution:</span>
                        <span class="metric-value"><?php echo $webcronStatus['datetime'] ?? 'Inconnue'; ?></span>
                    </div>
                    
                    <div class="metric">
                        <span>Statut:</span>
                        <span>
                            <span class="status-indicator status-<?php echo $webcronStatus['status']; ?>"></span>
                            <?php echo ucfirst($webcronStatus['status']); ?>
                        </span>
                    </div>
                    
                    <div class="metric">
                        <span>Alertes:</span>
                        <span class="metric-value"><?php echo count($webcronStatus['alerts'] ?? []); ?></span>
                    </div>
                    
                    <div class="metric">
                        <span>Temps d'exécution:</span>
                        <span class="metric-value"><?php echo $webcronStatus['execution_time_ms'] ?? 0; ?>ms</span>
                    </div>
                    
                    <div class="metric">
                        <span>IP Webcron:</span>
                        <span class="metric-value"><?php echo $monitoringData['webcron_status']['webcron_info']['ip'] ?? 'Inconnue'; ?></span>
                    </div>
                <?php else: ?>
                    <div class="error">Aucune donnée webcron disponible</div>
                <?php endif; ?>
            </div>
            
            <!-- Métriques Système -->
            <div class="card">
                <h3>⚙️ Métriques Système</h3>
                <?php if ($monitoringData['health_check'] && isset($monitoringData['health_check']['checks'])): ?>
                    <?php 
                    $checks = $monitoringData['health_check']['checks'];
                    $systemCheck = $checks['system'] ?? null;
                    ?>
                    
                    <?php if ($systemCheck && isset($systemCheck['details'])): ?>
                        <?php $details = $systemCheck['details']; ?>
                        
                        <?php if (isset($details['memory'])): ?>
                            <div class="metric">
                                <span>Mémoire:</span>
                                <span class="metric-value"><?php echo $details['memory']['usage']; ?> / <?php echo $details['memory']['limit']; ?> (<?php echo $details['memory']['percent']; ?>%)</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($details['disk'])): ?>
                            <div class="metric">
                                <span>Disque:</span>
                                <span class="metric-value"><?php echo $details['disk']['free']; ?> libre / <?php echo $details['disk']['total']; ?> (<?php echo $details['disk']['used_percent']; ?>% utilisé)</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="metric">
                            <span>Version PHP:</span>
                            <span class="metric-value"><?php echo PHP_VERSION; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="error">Données système non disponibles</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="error">Impossible de charger les métriques système</div>
                <?php endif; ?>
            </div>
            
            <!-- Alertes -->
            <div class="card">
                <h3>🚨 Alertes Actives</h3>
                <?php 
                $alerts = [];
                if ($monitoringData['webcron_status'] && isset($monitoringData['webcron_status']['last_check']['alerts'])) {
                    $alerts = $monitoringData['webcron_status']['last_check']['alerts'];
                }
                ?>
                
                <?php if (!empty($alerts)): ?>
                    <div class="alerts">
                        <h4>Alertes détectées:</h4>
                        <?php foreach ($alerts as $alert): ?>
                            <div class="alert-item">
                                <span class="alert-level alert-<?php echo $alert['level']; ?>"><?php echo $alert['level']; ?></span>
                                <strong><?php echo $alert['category']; ?>:</strong> <?php echo $alert['message']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="color: #4caf50; text-align: center; padding: 1rem;">
                        ✅ Aucune alerte active
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Historique -->
            <div class="card">
                <h3>📈 Historique (24h)</h3>
                <?php if ($monitoringData['webcron_status'] && isset($monitoringData['webcron_status']['history'])): ?>
                    <?php 
                    $history = array_slice($monitoringData['webcron_status']['history'], -48); // 48 dernières entrées
                    ?>
                    
                    <div class="metric">
                        <span>Nombre d'exécutions:</span>
                        <span class="metric-value"><?php echo count($history); ?></span>
                    </div>
                    
                    <div class="metric">
                        <span>Score moyen:</span>
                        <span class="metric-value">
                            <?php 
                            $avgScore = count($history) > 0 ? array_sum(array_column($history, 'health_score')) / count($history) : 0;
                            echo round($avgScore, 1);
                            ?>/100
                        </span>
                    </div>
                    
                    <div class="history-chart">
                        <?php foreach ($history as $entry): ?>
                            <div class="history-bar" style="height: <?php echo ($entry['health_score'] / 100) * 160; ?>px;" title="<?php echo date('H:i', $entry['timestamp']); ?> - <?php echo $entry['health_score']; ?>%"></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="error">Aucun historique disponible</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h3>🔧 Actions</h3>
            <button class="refresh-btn" onclick="location.reload()">🔄 Actualiser les données</button>
            <button class="refresh-btn" onclick="window.open('../api/health-check.php', '_blank')" style="margin-left: 1rem;">🏥 Health Check Direct</button>
            <button class="refresh-btn" onclick="window.open('../api/webcron-monitor.php?key=remmailleuse_monitor_2025', '_blank')" style="margin-left: 1rem;">🌐 Test Webcron</button>
        </div>
    </div>
    
    <!-- Bouton retour en haut -->
    <button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()" title="Retour en haut">
        ↑
    </button>
    
    <script>
        // Auto-refresh toutes les 30 secondes
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Indicateur de dernière actualisation
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            console.log('Dashboard chargé à ' + timeString);
        });
        
        // Gestion du bouton retour en haut
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Afficher/masquer le bouton selon la position de scroll
        window.addEventListener('scroll', function() {
            const scrollToTopBtn = document.getElementById('scrollToTop');
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });
        
        // Animation d'apparition progressive du contenu
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(function(card, index) {
                card.classList.add('loading-animation');
                card.style.animationDelay = (index * 0.1) + 's';
            });
            
            // Animation du titre
            const title = document.querySelector('.header h1');
            if (title) {
                title.style.animation = 'glow 2s ease-in-out infinite alternate';
            }
        });
    </script>
</body>
</html>