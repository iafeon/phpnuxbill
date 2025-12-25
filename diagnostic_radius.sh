#!/bin/bash

# Script de diagnostic complet FreeRADIUS et Mikrotik
# Pour identifier les problèmes persistants

echo "========================================"
echo "  DIAGNOSTIC FREERADIUS / MIKROTIK"
echo "========================================"
echo ""

# 1. Informations système
echo "## 1. INFORMATIONS SYSTÈME"
echo "-------------------------------------------"
echo "Date: $(date)"
echo "FreeRADIUS version: $(freeradius -v 2>&1 | head -1)"
echo ""

# 2. Statut FreeRADIUS
echo "## 2. STATUT FREERADIUS"
echo "-------------------------------------------"
systemctl status freeradius --no-pager | grep "Active:"
echo ""

# 3. Dernières erreurs FreeRADIUS
echo "## 3. DERNIÈRES ERREURS FREERADIUS (20 dernières)"
echo "-------------------------------------------"
tail -100 /var/log/freeradius/radius.log 2>/dev/null | grep -i "error\|reject\|fail" | tail -20
echo ""

# 4. Problèmes Message-Authenticator
echo "## 4. PROBLÈMES MESSAGE-AUTHENTICATOR"
echo "-------------------------------------------"
grep -i "message-authenticator\|blastradius" /var/log/freeradius/radius.log 2>/dev/null | tail -5
echo ""

# 5. Duplicate Packets
echo "## 5. DUPLICATE PACKETS"
echo "-------------------------------------------"
grep -i "duplicate packet" /var/log/freeradius/radius.log 2>/dev/null | tail -10
echo ""

# 6. Configuration NAS dans DB
echo "## 6. NAS CONFIGURÉS (BASE DE DONNÉES)"
echo "-------------------------------------------"
cd /var/www/phpnuxbill
php -r "include 'init.php'; \$nas = ORM::for_table('nas', 'radius')->find_array(); foreach(\$nas as \$n) { echo \"  - {\$n['shortname']} ({\$n['nasname']}) - Type: {\$n['type']}\\n\"; }"
echo ""

# 7. Vérifier clients.conf
echo "## 7. CONFIGURATION CLIENTS RADIUS"
echo "-------------------------------------------"
if [ -f /etc/freeradius/3.0/clients.conf ]; then
    echo "Fichier trouvé: /etc/freeradius/3.0/clients.conf"
    echo ""
    echo "Client localhost:"
    grep -A5 "client localhost" /etc/freeradius/3.0/clients.conf | head -10
    echo ""
    echo "Autres clients:"
    grep "^client " /etc/freeradius/3.0/clients.conf | grep -v localhost
else
    echo "❌ Fichier clients.conf non trouvé!"
fi
echo ""

# 8. Sessions actives
echo "## 8. SESSIONS ACTIVES RADIUS"
echo "-------------------------------------------"
php -r "include 'init.php'; \$sessions = ORM::for_table('radacct', 'radius')->where_raw('acctstoptime IS NULL')->count(); echo \"Total: \$sessions sessions actives\\n\";"
echo ""

# 9. Test utilisateurs avec attributs
echo "## 9. VÉRIFICATION ATTRIBUTS SHARED USERS"
echo "-------------------------------------------"
php -r "
include 'init.php';
\$users = ['tsiory', 'lebazol', 'tech'];
foreach(\$users as \$u) {
    \$simUse = ORM::for_table('radcheck', 'radius')->where('username', \$u)->where('attribute', 'Simultaneous-Use')->find_one();
    if (\$simUse) {
        echo \"  ✅ \$u: Simultaneous-Use = {\$simUse['value']}\\n\";
    } else {
        echo \"  ❌ \$u: Simultaneous-Use MANQUANT\\n\";
    }
}
"
echo ""

# 10. Derniers logs debug shared_users
echo "## 10. DERNIERS LOGS DEBUG SHARED USERS"
echo "-------------------------------------------"
if [ -f /tmp/shared_users_debug.log ]; then
    echo "Dernières 10 lignes:"
    tail -10 /tmp/shared_users_debug.log
else
    echo "Aucun log debug trouvé"
fi
echo ""

echo "========================================"
echo "  FIN DU DIAGNOSTIC"
echo "========================================"
echo ""
echo "RECOMMANDATIONS:"
echo "1. Vérifier configuration Mikrotik (/radius print)"
echo "2. Si erreurs Message-Authenticator: éditer /etc/freeradius/3.0/clients.conf"
echo "3. Si duplicate packets: augmenter timeout Mikrotik à 5s"
echo ""
