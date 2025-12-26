#!/bin/bash
#
# Configuration automatique des cron jobs PHPNuxBill
# Usage: sudo ./setup_cron.sh
#

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=== Configuration Cron Jobs PHPNuxBill ==="
echo ""

# Vérifier si root
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}⚠️  Ce script doit être exécuté en tant que root${NC}"
    echo "Utilisez: sudo $0"
    exit 1
fi

# Définir les cron jobs
CRON_USER="www-data"
PHPNUXBILL_DIR="/var/www/phpnuxbill"

# Créer un fichier temporaire pour les cron jobs
TEMP_CRON=$(mktemp)

# Récupérer les cron jobs existants
crontab -u "$CRON_USER" -l > "$TEMP_CRON" 2>/dev/null || echo "" > "$TEMP_CRON"

echo "[1/5] Configuration cron principal (exécution horaire)..."
# Vérifier si le cron existe déjà
if grep -q "${PHPNUXBILL_DIR}/system/cron.php" "$TEMP_CRON"; then
    echo "  Déjà configuré"
else
    echo "# PHPNuxBill - Cron principal (expiration, auto-renewal, monitoring)" >> "$TEMP_CRON"
    echo "0 * * * * cd ${PHPNUXBILL_DIR}/system && /usr/bin/php cron.php >> /var/log/phpnuxbill_cron.log 2>&1" >> "$TEMP_CRON"
    echo -e "  ${GREEN}✓${NC} Ajouté"
fi

echo ""
echo "[2/5] Configuration rappels d'expiration (quotidien à 7h00)..."
if grep -q "${PHPNUXBILL_DIR}/system/cron_reminder.php" "$TEMP_CRON"; then
    echo "  Déjà configuré"
else
    echo "# PHPNuxBill - Rappels d'expiration (1, 3, 7 jours avant)" >> "$TEMP_CRON"
    echo "0 7 * * * cd ${PHPNUXBILL_DIR}/system && /usr/bin/php cron_reminder.php >> /var/log/phpnuxbill_reminder.log 2>&1" >> "$TEMP_CRON"
    echo -e "  ${GREEN}✓${NC} Ajouté"
fi

echo ""
echo "[3/5] Configuration nettoyage logs (quotidien à 3h00)..."
if grep -q "${PHPNUXBILL_DIR}/scripts/cleanup_logs.sh" "$TEMP_CRON"; then
    echo "  Déjà configuré"
else
    echo "# PHPNuxBill - Nettoyage des logs" >> "$TEMP_CRON"
    echo "0 3 * * * ${PHPNUXBILL_DIR}/scripts/cleanup_logs.sh >> /var/log/phpnuxbill_cleanup.log 2>&1" >> "$TEMP_CRON"
    echo -e "  ${GREEN}✓${NC} Ajouté"
fi

echo ""
echo "[4/5] Configuration backup database (quotidien à 2h00)..."
if grep -q "${PHPNUXBILL_DIR}/scripts/backup_database.sh" "$TEMP_CRON"; then
    echo "  Déjà configuré"
else
    echo "# PHPNuxBill - Backup base de données" >> "$TEMP_CRON"
    echo "0 2 * * * ${PHPNUXBILL_DIR}/scripts/backup_database.sh >> /var/log/phpnuxbill_backup.log 2>&1" >> "$TEMP_CRON"
    echo -e "  ${GREEN}✓${NC} Ajouté"
fi

echo ""
echo "[5/5] Application de la configuration..."
crontab -u "$CRON_USER" "$TEMP_CRON"

if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓${NC} Configuration appliquée"
else
    echo "  ❌ Échec de la configuration"
    rm "$TEMP_CRON"
    exit 1
fi

# Nettoyer
rm "$TEMP_CRON"

# Afficher la configuration finale
echo ""
echo "=== Configuration Cron Actuelle ==="
crontab -u "$CRON_USER" -l | grep -v "^#" | grep -v "^$"

echo ""
echo -e "${GREEN}✓ Configuration terminée avec succès${NC}"
echo ""
echo "Les tâches configurées:"
echo "  • Cron principal: Toutes les heures"
echo "  • Rappels: Quotidien à 7h00"
echo "  • Nettoyage logs: Quotidien à 3h00"
echo "  • Backup DB: Quotidien à 2h00"
echo ""
echo "Logs disponibles:"
echo "  • /var/log/phpnuxbill_cron.log"
echo "  • /var/log/phpnuxbill_reminder.log"
echo "  • /var/log/phpnuxbill_cleanup.log"
echo "  • /var/log/phpnuxbill_backup.log"
