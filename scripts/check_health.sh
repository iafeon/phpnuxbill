#!/bin/bash
#
# Health Check PHPNuxBill
# Vérifie l'état du système et envoie des alertes si nécessaire
#

set -e

# Configuration
PHPNUXBILL_DIR="/var/www/phpnuxbill"
BACKUP_DIR="/backup/phpnuxbill"
DISK_THRESHOLD=80  # Alerte si disque > 80%

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

echo "=== PHPNuxBill Health Check ==="
echo "Date: $(date)"
echo ""

# 1. Vérifier l'espace disque
echo "[1/8] Espace disque..."
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    echo -e "  ${RED}❌ CRITIQUE:${NC} Disque utilisé à ${DISK_USAGE}%"
    ((ERRORS++))
else
    echo -e "  ${GREEN}✓${NC} OK (${DISK_USAGE}%)"
fi

# 2. Vérifier le dernier cron
echo ""
echo "[2/8] Dernière exécution cron..."
CRON_FILE="${PHPNUXBILL_DIR}/system/uploads/cron_last_run.txt"
if [ -f "$CRON_FILE" ]; then
    LAST_RUN=$(cat "$CRON_FILE")
    NOW=$(date +%s)
    DIFF=$((NOW - LAST_RUN))
    HOURS=$((DIFF / 3600))
    
    if [ $HOURS -gt 2 ]; then
        echo -e "  ${YELLOW}⚠️  ATTENTION:${NC} Dernier cron il y a ${HOURS}h"
        ((WARNINGS++))
    else
        echo -e "  ${GREEN}✓${NC} OK (il y a ${HOURS}h)"
    fi
else
    echo -e "  ${YELLOW}⚠️  ATTENTION:${NC} Fichier cron_last_run.txt introuvable"
    ((WARNINGS++))
fi

# 3. Vérifier la taille des logs
echo ""
echo "[3/8] Taille des logs de debug..."
LOG_SIZE=$(du -sm /tmp/radius_*.log 2>/dev/null | awk '{sum += $1} END {print sum}')
if [ -z "$LOG_SIZE" ]; then
    LOG_SIZE=0
fi

if [ "$LOG_SIZE" -gt 50 ]; then
    echo -e "  ${YELLOW}⚠️  ATTENTION:${NC} Logs volumineux (${LOG_SIZE}MB)"
    echo "    Exécutez: ./scripts/cleanup_logs.sh"
    ((WARNINGS++))
else
    echo -e "  ${GREEN}✓${NC} OK (${LOG_SIZE}MB)"
fi

# 4. Vérifier la base de données
echo ""
echo "[4/8] Connexion base de données..."
CONFIG_FILE="${PHPNUXBILL_DIR}/config.php"
if [ -f "$CONFIG_FILE" ]; then
    DB_HOST=$(grep -oP "\$db_host\s*=\s*'\K[^']+" "$CONFIG_FILE" || echo "localhost")
    DB_USER=$(grep -oP "\$db_user\s*=\s*'\K[^']+" "$CONFIG_FILE")
    DB_PASS=$(grep -oP "\$db_pass\s*=\s*'\K[^']+" "$CONFIG_FILE")
    DB_NAME=$(grep -oP "\$db_name\s*=\s*'\K[^']+" "$CONFIG_FILE")
    
    mysqladmin -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" ping &>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "  ${GREEN}✓${NC} OK"
    else
        echo -e "  ${RED}❌ CRITIQUE:${NC} Impossible de se connecter à MySQL"
        ((ERRORS++))
    fi
else
    echo -e "  ${RED}❌ CRITIQUE:${NC} config.php introuvable"
    ((ERRORS++))
fi

# 5. Vérifier les backups
echo ""
echo "[5/8] Backups récents..."
if [ -d "$BACKUP_DIR" ]; then
    LATEST_BACKUP=$(find "$BACKUP_DIR" -name "phpnuxbill_*.sql.gz" -type f -mtime -2 | head -1)
    
    if [ -n "$LATEST_BACKUP" ]; then
        echo -e "  ${GREEN}✓${NC} OK (backup récent trouvé)"
    else
        echo -e "  ${YELLOW}⚠️  ATTENTION:${NC} Aucun backup récent (< 48h)"
        ((WARNINGS++))
    fi
else
    echo -e "  ${YELLOW}⚠️  ATTENTION:${NC} Répertoire backup introuvable"
    ((WARNINGS++))
fi

# 6. Vérifier les permissions
echo ""
echo "[6/8] Permissions des fichiers..."
WRITABLE_DIRS=(
    "${PHPNUXBILL_DIR}/system/uploads"
    "${PHPNUXBILL_DIR}/qrcode"
)

PERM_OK=true
for dir in "${WRITABLE_DIRS[@]}"; do
    if [ ! -w "$dir" ]; then
        echo -e "  ${RED}❌${NC} $dir n'est pas accessible en écriture"
        PERM_OK=false
        ((ERRORS++))
    fi
done

if [ "$PERM_OK" = true ]; then
    echo -e "  ${GREEN}✓${NC} OK"
fi

# 7. Vérifier PHP
echo ""
echo "[7/8] Version PHP..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "  Version: $PHP_VERSION"

if php -m | grep -q opcache; then
    echo -e "  ${GREEN}✓${NC} OPcache activé"
else
    echo -e "  ${YELLOW}⚠️${NC} OPcache non activé (recommandé pour les performances)"
    ((WARNINGS++))
fi

# 8. Vérifier MySQL
echo ""
echo "[8/8] Version MySQL..."
if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | awk '{print $5}' | sed 's/,//')
    echo "  Version: $MYSQL_VERSION"
    echo -e "  ${GREEN}✓${NC} OK"
else
    echo -e "  ${RED}❌ CRITIQUE:${NC} MySQL non trouvé"
    ((ERRORS++))
fi

# Résumé
echo ""
echo "=== Résumé ==="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ Tous les tests sont passés${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  $WARNINGS avertissement(s)${NC}"
    exit 0
else
    echo -e "${RED}❌ $ERRORS erreur(s), $WARNINGS avertissement(s)${NC}"
    exit 1
fi
