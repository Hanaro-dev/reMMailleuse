#!/bin/bash

# ===== SCRIPT DE CONFIGURATION CRON - SITE REMMAILLEUSE =====
# Configure les tâches cron pour le nettoyage automatique
# Usage: ./setup-cron.sh [--install|--remove|--status]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CRON_SCRIPT="$SCRIPT_DIR/cleanup-cron.php"
CRON_USER=$(whoami)

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier si le script existe
check_script() {
    if [ ! -f "$CRON_SCRIPT" ]; then
        log_error "Script de nettoyage non trouvé: $CRON_SCRIPT"
        exit 1
    fi
    
    if [ ! -x "$CRON_SCRIPT" ]; then
        log_info "Rendre le script exécutable..."
        chmod +x "$CRON_SCRIPT"
    fi
}

# Installer les tâches cron
install_cron() {
    log_info "Installation des tâches cron pour le nettoyage automatique..."
    
    # Créer un fichier temporaire avec les nouvelles tâches
    TEMP_CRON=$(mktemp)
    
    # Récupérer les tâches existantes
    crontab -l 2>/dev/null | grep -v "# ReMmailleuse cleanup" > "$TEMP_CRON"
    
    # Ajouter les nouvelles tâches
    cat >> "$TEMP_CRON" << EOF

# ReMmailleuse cleanup - Nettoyage automatique
# Nettoyage rapide toutes les 30 minutes
*/30 * * * * /usr/bin/php "$CRON_SCRIPT" --type=quick >> "$PROJECT_DIR/logs/cron.log" 2>&1

# Nettoyage complet tous les jours à 2h du matin
0 2 * * * /usr/bin/php "$CRON_SCRIPT" --type=full >> "$PROJECT_DIR/logs/cron.log" 2>&1

# Nettoyage intelligent toutes les 2 heures
0 */2 * * * /usr/bin/php "$CRON_SCRIPT" --type=smart >> "$PROJECT_DIR/logs/cron.log" 2>&1

EOF
    
    # Installer les tâches
    crontab "$TEMP_CRON"
    
    if [ $? -eq 0 ]; then
        log_success "Tâches cron installées avec succès"
        log_info "Tâches programmées:"
        log_info "  - Nettoyage rapide: toutes les 30 minutes"
        log_info "  - Nettoyage complet: tous les jours à 2h"
        log_info "  - Nettoyage intelligent: toutes les 2 heures"
    else
        log_error "Erreur lors de l'installation des tâches cron"
        exit 1
    fi
    
    # Nettoyer
    rm -f "$TEMP_CRON"
}

# Supprimer les tâches cron
remove_cron() {
    log_info "Suppression des tâches cron de nettoyage..."
    
    # Créer un fichier temporaire sans les tâches de nettoyage
    TEMP_CRON=$(mktemp)
    crontab -l 2>/dev/null | grep -v "ReMmailleuse cleanup" | grep -v "$CRON_SCRIPT" > "$TEMP_CRON"
    
    # Réinstaller les tâches nettoyées
    crontab "$TEMP_CRON"
    
    if [ $? -eq 0 ]; then
        log_success "Tâches cron supprimées avec succès"
    else
        log_error "Erreur lors de la suppression des tâches cron"
        exit 1
    fi
    
    # Nettoyer
    rm -f "$TEMP_CRON"
}

# Afficher le statut des tâches cron
show_status() {
    log_info "Statut des tâches cron de nettoyage:"
    echo
    
    # Vérifier si des tâches existent
    CRON_COUNT=$(crontab -l 2>/dev/null | grep -c "$CRON_SCRIPT")
    
    if [ "$CRON_COUNT" -gt 0 ]; then
        log_success "$CRON_COUNT tâche(s) cron installée(s)"
        echo
        echo "Tâches actives:"
        crontab -l 2>/dev/null | grep "$CRON_SCRIPT" | while read line; do
            echo "  $line"
        done
    else
        log_warning "Aucune tâche cron installée"
    fi
    
    echo
    
    # Vérifier les logs récents
    LOG_FILE="$PROJECT_DIR/logs/cron.log"
    if [ -f "$LOG_FILE" ]; then
        log_info "Dernières exécutions (10 dernières lignes):"
        tail -n 10 "$LOG_FILE"
    else
        log_info "Aucun log d'exécution trouvé"
    fi
}

# Tester le script de nettoyage
test_cleanup() {
    log_info "Test du script de nettoyage..."
    
    # Exécuter le script en mode verbose
    /usr/bin/php "$CRON_SCRIPT" --type=quick --verbose
    
    if [ $? -eq 0 ]; then
        log_success "Test de nettoyage réussi"
    else
        log_error "Test de nettoyage échoué"
        exit 1
    fi
}

# Afficher l'aide
show_help() {
    echo "Usage: $0 [OPTION]"
    echo
    echo "Options:"
    echo "  --install    Installer les tâches cron de nettoyage"
    echo "  --remove     Supprimer les tâches cron de nettoyage"
    echo "  --status     Afficher le statut des tâches cron"
    echo "  --test       Tester le script de nettoyage"
    echo "  --help       Afficher cette aide"
    echo
    echo "Exemple:"
    echo "  $0 --install     # Installe les tâches cron"
    echo "  $0 --status      # Affiche le statut"
    echo "  $0 --remove      # Supprime les tâches cron"
}

# Vérifier les prérequis
check_requirements() {
    # Vérifier que PHP est installé
    if ! command -v php &> /dev/null; then
        log_error "PHP n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi
    
    # Vérifier que cron est installé
    if ! command -v crontab &> /dev/null; then
        log_error "crontab n'est pas installé"
        exit 1
    fi
    
    # Vérifier les permissions
    if [ ! -w "$PROJECT_DIR/logs" ]; then
        log_info "Création du dossier logs..."
        mkdir -p "$PROJECT_DIR/logs"
    fi
}

# Script principal
main() {
    log_info "Configuration des tâches cron pour ReMmailleuse"
    echo
    
    # Vérifier les prérequis
    check_requirements
    check_script
    
    # Parser les arguments
    case "${1:-}" in
        --install)
            install_cron
            ;;
        --remove)
            remove_cron
            ;;
        --status)
            show_status
            ;;
        --test)
            test_cleanup
            ;;
        --help|-h)
            show_help
            ;;
        "")
            log_info "Aucune option spécifiée. Utiliser --help pour l'aide."
            show_status
            ;;
        *)
            log_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
}

# Exécuter le script principal
main "$@"