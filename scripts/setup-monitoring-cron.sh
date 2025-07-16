#!/bin/bash

# ===== CONFIGURATION CRON MONITORING - SITE REMMAILLEUSE =====
# Script pour configurer la surveillance automatique via cron
# 
# @author  Développeur Site Remmailleuse
# @version 1.0
# @date    15 juillet 2025

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MONITOR_SCRIPT="$SCRIPT_DIR/monitor.php"
CRON_LOG="/var/log/remmailleuse-monitoring.log"
PHP_PATH="/usr/bin/php"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction d'affichage
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier si le script est exécuté en tant que root ou avec les bonnes permissions
check_permissions() {
    if [ ! -w /var/spool/cron/crontabs ] && [ ! -w /var/spool/cron ]; then
        print_error "Permissions insuffisantes pour modifier le crontab"
        print_error "Exécutez avec sudo ou en tant qu'utilisateur propriétaire"
        exit 1
    fi
}

# Vérifier les prérequis
check_requirements() {
    print_status "Vérification des prérequis..."
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi
    
    # Vérifier le script de monitoring
    if [ ! -f "$MONITOR_SCRIPT" ]; then
        print_error "Script de monitoring non trouvé: $MONITOR_SCRIPT"
        exit 1
    fi
    
    # Vérifier que le script est exécutable
    if [ ! -x "$MONITOR_SCRIPT" ]; then
        print_status "Ajout des permissions d'exécution au script de monitoring"
        chmod +x "$MONITOR_SCRIPT"
    fi
    
    # Créer le répertoire de logs si nécessaire
    if [ ! -d "$(dirname "$CRON_LOG")" ]; then
        print_status "Création du répertoire de logs: $(dirname "$CRON_LOG")"
        sudo mkdir -p "$(dirname "$CRON_LOG")"
    fi
    
    # Tester le script de monitoring
    print_status "Test du script de monitoring..."
    if ! php "$MONITOR_SCRIPT" > /dev/null 2>&1; then
        print_warning "Le script de monitoring a signalé des problèmes, mais cela peut être normal"
    fi
    
    print_status "Prérequis vérifiés avec succès"
}

# Configurer le crontab
setup_cron() {
    print_status "Configuration du crontab..."
    
    # Créer une sauvegarde du crontab actuel
    crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
    
    # Commande cron pour le monitoring
    CRON_COMMAND="$PHP_PATH $MONITOR_SCRIPT >> $CRON_LOG 2>&1"
    
    # Différentes fréquences de monitoring
    echo "Choisissez la fréquence de monitoring:"
    echo "1) Toutes les 5 minutes (recommandé pour la production)"
    echo "2) Toutes les 10 minutes"
    echo "3) Toutes les 15 minutes"
    echo "4) Toutes les 30 minutes"
    echo "5) Toutes les heures"
    echo "6) Personnalisé"
    
    read -p "Votre choix (1-6): " choice
    
    case $choice in
        1)
            CRON_SCHEDULE="*/5 * * * *"
            ;;
        2)
            CRON_SCHEDULE="*/10 * * * *"
            ;;
        3)
            CRON_SCHEDULE="*/15 * * * *"
            ;;
        4)
            CRON_SCHEDULE="*/30 * * * *"
            ;;
        5)
            CRON_SCHEDULE="0 * * * *"
            ;;
        6)
            read -p "Entrez votre expression cron (ex: */5 * * * *): " CRON_SCHEDULE
            ;;
        *)
            print_error "Choix invalide"
            exit 1
            ;;
    esac
    
    # Ajouter la tâche cron
    CRON_ENTRY="$CRON_SCHEDULE $CRON_COMMAND"
    
    # Vérifier si la tâche existe déjà
    if crontab -l 2>/dev/null | grep -q "$MONITOR_SCRIPT"; then
        print_warning "Une tâche cron existe déjà pour ce script"
        read -p "Voulez-vous la remplacer? (y/n): " replace
        if [ "$replace" = "y" ] || [ "$replace" = "Y" ]; then
            # Supprimer l'ancienne tâche
            crontab -l 2>/dev/null | grep -v "$MONITOR_SCRIPT" | crontab -
        else
            print_status "Configuration annulée"
            exit 0
        fi
    fi
    
    # Ajouter la nouvelle tâche
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    
    print_status "Tâche cron ajoutée: $CRON_ENTRY"
}

# Créer un script de gestion
create_management_script() {
    MANAGEMENT_SCRIPT="$SCRIPT_DIR/monitoring-control.sh"
    
    cat > "$MANAGEMENT_SCRIPT" << 'EOF'
#!/bin/bash

# Script de gestion du monitoring ReMmailleuse
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MONITOR_SCRIPT="$SCRIPT_DIR/monitor.php"
CRON_LOG="/var/log/remmailleuse-monitoring.log"

case "$1" in
    start)
        echo "Démarrage du monitoring..."
        # Le monitoring est géré par cron, vérifier qu'il est actif
        if crontab -l 2>/dev/null | grep -q "$MONITOR_SCRIPT"; then
            echo "Monitoring actif dans le crontab"
        else
            echo "Monitoring non configuré dans le crontab"
            exit 1
        fi
        ;;
    stop)
        echo "Arrêt du monitoring..."
        crontab -l 2>/dev/null | grep -v "$MONITOR_SCRIPT" | crontab -
        echo "Monitoring retiré du crontab"
        ;;
    status)
        echo "Statut du monitoring:"
        if crontab -l 2>/dev/null | grep -q "$MONITOR_SCRIPT"; then
            echo "✅ Monitoring actif"
            echo "Tâche cron:"
            crontab -l 2>/dev/null | grep "$MONITOR_SCRIPT"
        else
            echo "❌ Monitoring inactif"
        fi
        ;;
    test)
        echo "Test du monitoring..."
        php "$MONITOR_SCRIPT"
        ;;
    logs)
        echo "Logs de monitoring:"
        if [ -f "$CRON_LOG" ]; then
            tail -n 50 "$CRON_LOG"
        else
            echo "Aucun log trouvé"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|status|test|logs}"
        exit 1
        ;;
esac
EOF

    chmod +x "$MANAGEMENT_SCRIPT"
    print_status "Script de gestion créé: $MANAGEMENT_SCRIPT"
}

# Créer un script de rotation des logs
create_log_rotation() {
    LOGROTATE_CONFIG="/etc/logrotate.d/remmailleuse-monitoring"
    
    if [ -w "/etc/logrotate.d" ]; then
        print_status "Configuration de la rotation des logs..."
        
        sudo tee "$LOGROTATE_CONFIG" > /dev/null << EOF
$CRON_LOG {
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
    create 644 $(whoami) $(whoami)
}
EOF
        
        print_status "Rotation des logs configurée: $LOGROTATE_CONFIG"
    else
        print_warning "Impossible de configurer la rotation des logs (permissions insuffisantes)"
    fi
}

# Afficher un résumé
show_summary() {
    print_status "=== RÉSUMÉ DE LA CONFIGURATION ==="
    echo "Script de monitoring: $MONITOR_SCRIPT"
    echo "Fichier de log: $CRON_LOG"
    echo "Fréquence: $CRON_SCHEDULE"
    echo ""
    echo "Commandes utiles:"
    echo "- Voir les logs: tail -f $CRON_LOG"
    echo "- Tester manuellement: php $MONITOR_SCRIPT"
    echo "- Gérer le monitoring: $SCRIPT_DIR/monitoring-control.sh {start|stop|status|test|logs}"
    echo ""
    print_status "Monitoring configuré avec succès!"
}

# Fonction principale
main() {
    echo "=== CONFIGURATION MONITORING REMMAILLEUSE ==="
    echo ""
    
    check_permissions
    check_requirements
    setup_cron
    create_management_script
    create_log_rotation
    show_summary
}

# Exécuter si le script est appelé directement
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi