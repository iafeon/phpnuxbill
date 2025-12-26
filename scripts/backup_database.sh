#!/bin/bash
#
# Script de backup automatique MySQL pour PHPNuxBill
# Usage: ./backup_database.sh
#

set -e

# Configuration
BACKUP_DIR="/backup/phpnuxbill"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=== Backup PHPNuxBill Database ==="
echo "Date: $(date)"
echo ""

# Créer le répertoire de backup
mkdir -p "$BACKUP_DIR"

# Lire les credentials depuis config.php
CONFIG_FILE="/var/www/phpnuxbill/config.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${RED}❌ Fichier config.php non trouvé${NC}"
    exit 1
fi

# Extraire les informations de connexion
DB_HOST=$(grep -oP "\$db_host\s*=\s*'\K[^']+" "$CONFIG_FILE" || echo "localhost")
DB_USER=$(grep -oP "\$db_user\s*=\s*'\K[^']+" "$CONFIG_FILE")
DB_PASS=$(grep -oP "\$db_pass\s*=\s*'\K[^']+" "$CONFIG_FILE")
DB_NAME=$(grep -oP "\$db_name\s*=\s*'\K[^']+" "$CONFIG_FILE")

if [ -z "$DB_USER" ] || [ -z "$DB_NAME" ]; then
    echo -e "${RED}❌ Impossible de lire les credentials de la base de données${NC}"
    exit 1
fi

echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""

# Backup filename
BACKUP_FILE="${BACKUP_DIR}/phpnuxbill_${DB_NAME}_${DATE}.sql"
BACKUP_FILE_GZ="${BACKUP_FILE}.gz"

# Effectuer le backup
echo "[1/4] Création du backup..."
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null

if [ $? -eq 0 ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo -e "  ${GREEN}✓${NC} Backup créé: $BACKUP_SIZE"
else
    echo -e "  ${RED}❌${NC} Échec du backup"
    exit 1
fi

# Compresser
echo ""
echo "[2/4] Compression..."
gzip "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    COMPRESSED_SIZE=$(du -h "$BACKUP_FILE_GZ" | cut -f1)
    echo -e "  ${GREEN}✓${NC} Compressé: $COMPRESSED_SIZE"
else
    echo -e "  ${RED}❌${NC} Échec de la compression"
    exit 1
fi

# Vérifier l'intégrité
echo ""
echo "[3/4] Vérification de l'intégrité..."
gunzip -t "$BACKUP_FILE_GZ"

if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓${NC} Intégrité vérifiée"
else
    echo -e "  ${RED}❌${NC} Fichier corrompu"
    exit 1
fi

# Nettoyer les anciens backups
echo ""
echo "[4/4] Nettoyage des anciens backups (> $RETENTION_DAYS jours)..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "phpnuxbill_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete -print | wc -l)

if [ $DELETED_COUNT -gt 0 ]; then
    echo -e "  ${GREEN}✓${NC} $DELETED_COUNT ancien(s) backup(s) supprimé(s)"
else
    echo "  Aucun ancien backup à supprimer"
fi

# Résumé
echo ""
echo -e "${GREEN}✓ Backup terminé avec succès${NC}"
echo ""
echo "Fichier: $(basename $BACKUP_FILE_GZ)"
echo "Taille: $COMPRESSED_SIZE"
echo "Emplacement: $BACKUP_DIR"

# Lister les backups disponibles
echo ""
echo "Backups disponibles:"
ls -lht "$BACKUP_DIR"/ | grep phpnuxbill_ | head -5

# Log
echo "$(date '+%Y-%m-%d %H:%M:%S') - Backup successful: $BACKUP_FILE_GZ" >> "$BACKUP_DIR/backup.log"
