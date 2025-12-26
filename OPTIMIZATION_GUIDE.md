# Guide d'Optimisation PHPNuxBill

Ce guide contient toutes les optimisations appliquées à votre installation PHPNuxBill.

## 📦 Scripts Installés

### 1. Nettoyage des Logs
**Fichier**: `scripts/cleanup_logs.sh`
**Fréquence**: Quotidien (3h00)
**Action**: Nettoie les logs de debug volumineux

**Utilisation manuelle**:
```bash
cd /var/www/phpnuxbill
./scripts/cleanup_logs.sh           # Nettoyage réel
./scripts/cleanup_logs.sh --dry-run  # Simulation
```

### 2. Backup Base de Données
**Fichier**: `scripts/backup_database.sh`
**Fréquence**: Quotidien (2h00)
**Action**: Sauvegarde complète de MySQL
**Rétention**: 30 jours

**Utilisation manuelle**:
```bash
cd /var/www/phpnuxbill
./scripts/backup_database.sh
```

**Restaurer un backup**:
```bash
cd /backup/phpnuxbill
gunzip < phpnuxbill_database_YYYYMMDD_HHMMSS.sql.gz | mysql -u user -p database_name
```

### 3. Health Check
**Fichier**: `scripts/check_health.sh`
**Fréquence**: À la demande
**Action**: Vérifie l'état du système

**Utilisation**:
```bash
cd /var/www/phpnuxbill
./scripts/check_health.sh
```

### 4. Optimisation Base de Données
**Fichier**: `scripts/database_optimize.sql`
**Fréquence**: Mensuel (manuel)
**Action**: Optimise les index et nettoie les données

**Utilisation**:
```bash
# Lire les credentials depuis config.php
DB_USER=$(grep -oP "\$db_user\s*=\s*'\K[^']+" config.php)
DB_NAME=$(grep -oP "\$db_name\s*=\s*'\K[^']+" config.php)

mysql -u $DB_USER -p $DB_NAME < scripts/database_optimize.sql
```

## ⏰ Cron Jobs Configurés

Tous les cron jobs sont configurés pour l'utilisateur `www-data`:

```cron
# Cron principal - Toutes les heures
0 * * * * /usr/bin/php /var/www/phpnuxbill/system/cron.php

# Rappels d'expiration - Quotidien à 7h00
0 7 * * * /usr/bin/php /var/www/phpnuxbill/system/cron_reminder.php

# Nettoyage logs - Quotidien à 3h00
0 3 * * * /var/www/phpnuxbill/scripts/cleanup_logs.sh

# Backup DB - Quotidien à 2h00
0 2 * * * /var/www/phpnuxbill/scripts/backup_database.sh
```

**Vérifier les cron jobs**:
```bash
sudo crontab -u www-data -l
```

## 📊 Monitoring

### Logs à Surveiller

1. **Cron principal**: `/var/log/phpnuxbill_cron.log`
2. **Rappels**: `/var/log/phpnuxbill_reminder.log`
3. **Nettoyage**: `/var/log/phpnuxbill_cleanup.log`
4. **Backups**: `/var/log/phpnuxbill_backup.log`

**Voir les dernières lignes**:
```bash
tail -f /var/log/phpnuxbill_cron.log
```

### Dernière Exécution Cron

```bash
cat /var/www/phpnuxbill/system/uploads/cron_last_run.txt
date -d @$(cat /var/www/phpnuxbill/system/uploads/cron_last_run.txt)
```

## 🔧 Optimisations Appliquées

### Base de Données

✅ **Index ajoutés** pour améliorer les performances:
- `tbl_user_recharges`: index sur status, expiration, customer_id
- `rad_acct`: index sur username, acctstatustype, dateAdded
- `tbl_customers`: index sur username, email, status
- `tbl_plans`: index sur enabled, routers
- `tbl_transactions`: index sur customer_id, invoice

✅ **Nettoyage automatique**:
- Sessions RADIUS anciennes (> 90 jours) supprimées

### PHP

✅ **OPcache activé**: Améliore les performances de 30-50%
✅ **Configuration optimale**: memory_limit, max_execution_time

### Fichiers

✅ **Rotation des logs**: Logs limités automatiquement
✅ **Nettoyage cache**: Fichiers anciens supprimés
✅ **Backups automatiques**: Sauvegarde quotidienne

## 🎯 Checklist Maintenance Mensuelle

- [ ] Vérifier les backups: `ls -lh /backup/phpnuxbill/`
- [ ] Exécuter health check: `./scripts/check_health.sh`
- [ ] Optimiser la base de données: `mysql ... < scripts/database_optimize.sql`
- [ ] Vérifier les logs d'erreur: `grep -i error /var/log/phpnuxbill_*.log`
- [ ] Tester une restauration de backup
- [ ] Vérifier l'espace disque: `df -h`

## 📈 Métriques de Performance

### Avant Optimisation
- Taille logs: ~8MB accumulés
- Cron jobs: ❌ Non configurés
- Backups: ❌  Aucun
- Index DB: ⚠️ Manquants

### Après Optimisation
- Taille logs: < 1MB (rotation automatique)
- Cron jobs: ✅ Actifs
- Backups: ✅ Quotidiens (rétention 30 jours)
- Index DB: ✅ Optimisés

## 🆘 Dépannage

### Le cron ne s'exécute pas

```bash
# Vérifier le service cron
sudo systemctl status cron

# Redémarrer le service cron
sudo systemctl restart cron

# Vérifier les cron jobs de www-data
sudo crontab -u www-data -l

# Exécuter manuellement pour tester
sudo -u www-data /usr/bin/php /var/www/phpnuxbill/system/cron.php
```

### Le backup échoue

```bash
# Vérifier les permissions
ls -l /backup/phpnuxbill/

# Créer le répertoire si nécessaire
sudo mkdir -p /backup/phpnuxbill
sudo chown www-data:www-data /backup/phpnuxbill

# Tester manuellement
sudo -u www-data /var/www/phpnuxbill/scripts/backup_database.sh
```

### Logs volumineux

```bash
# Nettoyage immédiat
./scripts/cleanup_logs.sh

# Désactiver les logs de debug (production)
# Éditer radius.php et commenter les lignes file_put_contents
```

## 🔒 Sécurité

### Permissions Recommandées

```bash
# Répertoires
chmod 755 /var/www/phpnuxbill
chmod 775 /var/www/phpnuxbill/system/uploads
chmod 775 /var/www/phpnuxbill/qrcode

# Fichiers sensibles
chmod 640 /var/www/phpnuxbill/config.php
chown www-data:www-data /var/www/phpnuxbill/config.php
```

### Backups

- Stocker les backups hors du serveur web
- Chiffrer les backups contenant des données sensibles
- Tester régulièrement la restauration

## 📞 Support

Pour toute question:
- [Documentation PHPNuxBill](https://github.com/hotspotbilling/phpnuxbill/wiki)
- [Telegram Group](https://t.me/phpnuxbill)
