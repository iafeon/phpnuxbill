#!/bin/bash
#
# Auto-fix pour nouveaux vouchers
# Exécute le script de fix toutes les heures
#

LOG_FILE="/var/www/phpnuxbill/voucher_autofix.log"

echo "$(date '+%Y-%m-%d %H:%M:%S') - Démarrage auto-fix vouchers" >> "$LOG_FILE"

cd /var/www/phpnuxbill

# Exécuter le script de fix
OUTPUT=$(php fix_ALL_vouchers_radcheck.php 2>&1)

# Logger le résultat
echo "$OUTPUT" >> "$LOG_FILE"

# Extraire le nombre de vouchers fixés
FIXED=$(echo "$OUTPUT" | grep "Vouchers fixés:" | awk '{print $3}')

if [ ! -z "$FIXED" ] && [ "$FIXED" -gt 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ✅ $FIXED nouveaux vouchers fixés" >> "$LOG_FILE"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Aucun nouveau voucher à fixer" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"
