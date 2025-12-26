#!/bin/bash
#
# Script de nettoyage des logs PHPNuxBill
# Usage: ./cleanup_logs.sh [--dry-run]
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHPNUXBILL_DIR="/var/www/phpnuxbill"
LOG_DIR="/tmp"
DRY_RUN=false

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

if [[ "$1" == "--dry-run" ]]; then
    DRY_RUN=true
    echo -e "${YELLOW}[DRY RUN MODE]${NC} Aucune modification ne sera effectuée"
    echo ""
fi

echo "=== Nettoyage des Logs PHPNuxBill ==="
echo "Date: $(date)"
echo ""

# Fonction pour afficher la taille d'un fichier
show_size() {
    if [ -f "$1" ]; then
        ls -lh "$1" | awk '{print $5}'
    else
        echo "0"
    fi
}

# Logs RADIUS de debug
echo "[1/4] Logs RADIUS de debug..."
RADIUS_LOGS=(
    "${LOG_DIR}/radius_request_debug.log"
    "${LOG_DIR}/radius_debug_authorize.log"
    "${LOG_DIR}/radius_debug_accounting.log"
    "${LOG_DIR}/radius_debug.log"
    "${LOG_DIR}/radius_alert.log"
    "${LOG_DIR}/radius_debug_detailed.log"
)

TOTAL_SIZE=0
for log in "${RADIUS_LOGS[@]}"; do
    if [ -f "$log" ]; then
        SIZE=$(show_size "$log")
        echo "  - $(basename $log): $SIZE"
        
        if [ "$DRY_RUN" = false ]; then
            # Archiver les 100 dernières lignes, supprimer le reste
            tail -n 100 "$log" > "${log}.tmp"
            mv "${log}.tmp" "$log"
            echo -e "    ${GREEN}✓${NC} Nettoyé (conservé 100 dernières lignes)"
        else
            echo -e "    ${YELLOW}[DRY RUN]${NC} Serait nettoyé"
        fi
    fi
done

# Logs PHP temporaires
echo ""
echo "[2/4] Logs PHP temporaires..."
find "${LOG_DIR}" -name "php_errors_*.log" -type f -mtime +7 2>/dev/null | while read logfile; do
    SIZE=$(show_size "$logfile")
    echo "  - $(basename $logfile): $SIZE"
    
    if [ "$DRY_RUN" = false ]; then
        rm -f "$logfile"
        echo -e "    ${GREEN}✓${NC} Supprimé"
    else
        echo -e "    ${YELLOW}[DRY RUN]${NC} Serait supprimé"
    fi
done

# Fichiers cache anciens
echo ""
echo "[3/4] Fichiers cache anciens (> 30 jours)..."
CACHE_DIR="${PHPNUXBILL_DIR}/system/cache"
if [ -d "$CACHE_DIR" ]; then
    COUNT=$(find "$CACHE_DIR" -type f -mtime +30 2>/dev/null | wc -l)
    echo "  Fichiers trouvés: $COUNT"
    
    if [ $COUNT -gt 0 ]; then
        if [ "$DRY_RUN" = false ]; then
            find "$CACHE_DIR" -type f -mtime +30 -delete 2>/dev/null
            echo -e "  ${GREEN}✓${NC} Supprimés"
        else
            echo -e "  ${YELLOW}[DRY RUN]${NC} Seraient supprimés"
        fi
    fi
else
    echo "  Répertoire cache non trouvé"
fi

# Sessions temporaires
echo ""
echo "[4/4] Sessions temporaires..."
SESSION_DIR="${PHPNUXBILL_DIR}/system/uploads/_sysfrm_tmp_"
if [ -d "$SESSION_DIR" ]; then
    COUNT=$(find "$SESSION_DIR" -type f -mtime +7 2>/dev/null | wc -l)
    echo "  Fichiers trouvés: $COUNT"
    
    if [ $COUNT -gt 0 ]; then
        if [ "$DRY_RUN" = false ]; then
            find "$SESSION_DIR" -type f -mtime +7 -delete 2>/dev/null
            echo -e "  ${GREEN}✓${NC} Supprimés"
        else
            echo -e "  ${YELLOW}[DRY RUN]${NC} Seraient supprimés"
        fi
    fi
else
    echo "  Répertoire non trouvé"
fi

echo ""
if [ "$DRY_RUN" = false ]; then
    echo -e "${GREEN}✓ Nettoyage terminé avec succès${NC}"
else
    echo -e "${YELLOW}[DRY RUN] Exécutez sans --dry-run pour effectuer les modifications${NC}"
fi

# Log this cleanup
echo "$(date '+%Y-%m-%d %H:%M:%S') - Cleanup executed" >> "${PHPNUXBILL_DIR}/system/uploads/cleanup_log.txt"
