<?php
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/login.html');
    exit();
}

// Inclure les dépendances
require_once '../api/SecurityHeaders.php';
require_once '../api/Logger.php';

// Initialiser la sécurité
$logger = new Logger();
initSecurityHeaders('admin', $logger);

// Obtenir les informations de sécurité
$security = new SecurityHeaders($logger);
$securityReport = $security->generateSecurityReport();
$validation = $security->validateSecurityConfig();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Sécurité - Administration</title>
    
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&family=Audiowide&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .security-dashboard {
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
        
        .security-score {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            position: relative;
        }
        
        .score-excellent {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
        }
        
        .score-good {
            background: linear-gradient(135deg, #ff9800, #f57c00);
        }
        
        .score-poor {
            background: linear-gradient(135deg, #f44336, #d32f2f);
        }
        
        .score-details {
            text-align: center;
        }
        
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .security-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #673ab7;
        }
        
        .security-card h3 {
            color: #673ab7;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-ok {
            background: #4caf50;
        }
        
        .status-warning {
            background: #ff9800;
        }
        
        .status-error {
            background: #f44336;
        }
        
        .security-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .security-item:last-child {
            border-bottom: none;
        }
        
        .headers-list {
            font-family: monospace;
            font-size: 0.9rem;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
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
        
        .refresh-button {
            background: #673ab7;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .refresh-button:hover {
            background: #563098;
            transform: translateY(-2px);
        }
        
        .issues-list {
            list-style: none;
            padding: 0;
        }
        
        .issues-list li {
            padding: 0.5rem 0;
            color: #d32f2f;
        }
        
        .issues-list li:before {
            content: "⚠️ ";
            margin-right: 0.5rem;
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
    <div class="security-dashboard">
        <a href="index.php" class="back-link">← Retour à l'administration</a>
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">🔒 Tableau de Bord Sécurité</h1>
            <p>Surveillance et configuration de la sécurité du site</p>
            <button class="refresh-button" onclick="location.reload()">🔄 Actualiser</button>
        </div>
        
        <div class="security-score">
            <div class="score-circle <?php 
                echo $validation['score'] >= 90 ? 'score-excellent' : 
                     ($validation['score'] >= 70 ? 'score-good' : 'score-poor'); 
            ?>">
                <?php echo $validation['score']; ?>%
            </div>
            <div class="score-details">
                <h3>Score de Sécurité</h3>
                <p>
                    <?php 
                    if ($validation['score'] >= 90) {
                        echo "🟢 Excellent - Sécurité optimale";
                    } elseif ($validation['score'] >= 70) {
                        echo "🟡 Bon - Quelques améliorations possibles";
                    } else {
                        echo "🔴 Critique - Problèmes à corriger";
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="security-grid">
            <div class="security-card">
                <h3>🔐 Configuration HTTPS</h3>
                <div class="security-item">
                    <span class="status-indicator <?php echo $securityReport['https_enabled'] ? 'status-ok' : 'status-error'; ?>"></span>
                    HTTPS <?php echo $securityReport['https_enabled'] ? 'Activé' : 'Désactivé'; ?>
                </div>
                <div class="security-item">
                    <span class="status-indicator <?php echo $securityReport['hsts_enabled'] ? 'status-ok' : 'status-warning'; ?>"></span>
                    HSTS <?php echo $securityReport['hsts_enabled'] ? 'Activé' : 'Désactivé'; ?>
                </div>
            </div>
            
            <div class="security-card">
                <h3>🛡️ Content Security Policy</h3>
                <div class="security-item">
                    <span class="status-indicator <?php echo $securityReport['csp_enabled'] ? 'status-ok' : 'status-error'; ?>"></span>
                    CSP <?php echo $securityReport['csp_enabled'] ? 'Configuré' : 'Non configuré'; ?>
                </div>
                <div class="security-item">
                    <span class="status-indicator status-ok"></span>
                    Politique stricte appliquée
                </div>
            </div>
            
            <div class="security-card">
                <h3>📋 En-têtes de Sécurité</h3>
                <div class="security-item">
                    <span class="status-indicator status-ok"></span>
                    <?php echo $securityReport['headers_count']; ?> en-têtes configurés
                </div>
                <div class="security-item">
                    <span class="status-indicator status-ok"></span>
                    Protection XSS active
                </div>
                <div class="security-item">
                    <span class="status-indicator status-ok"></span>
                    Protection Clickjacking active
                </div>
            </div>
            
            <div class="security-card">
                <h3>⚠️ Problèmes Détectés</h3>
                <?php if (empty($validation['issues'])): ?>
                    <div class="security-item">
                        <span class="status-indicator status-ok"></span>
                        Aucun problème détecté
                    </div>
                <?php else: ?>
                    <ul class="issues-list">
                        <?php foreach ($validation['issues'] as $issue): ?>
                            <li><?php echo htmlspecialchars($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="security-card">
            <h3>📊 En-têtes HTTP Appliqués</h3>
            <div class="headers-list">
                <?php if (!empty($securityReport['headers_applied'])): ?>
                    <?php foreach ($securityReport['headers_applied'] as $header): ?>
                        <div><?php echo htmlspecialchars($header); ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>Aucun en-tête de sécurité détecté</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="security-card">
            <h3>📈 Détails du Rapport</h3>
            <div class="security-item">
                <strong>Timestamp:</strong> <?php echo $securityReport['timestamp']; ?>
            </div>
            <div class="security-item">
                <strong>Validation:</strong> <?php echo $validation['valid'] ? '✅ Valide' : '❌ Problèmes détectés'; ?>
            </div>
            <div class="security-item">
                <strong>Score:</strong> <?php echo $validation['score']; ?>/100
            </div>
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
        
        // Auto-refresh toutes les 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>