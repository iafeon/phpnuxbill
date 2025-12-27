#!/bin/bash

echo "=== ANALYSE DES LOGOS PHPNUXBILL ==="
echo ""

# Vérifier les fichiers logo existants
echo "📁 FICHIERS LOGO TROUVÉS:"
echo "----------------------------------------"
find /var/www/phpnuxbill -name "logo.png" -o -name "logo.jpg" -o -name "login-logo.png" 2>/dev/null | while read file; do
    if [ -f "$file" ]; then
        size=$(stat -c%s "$file")
        perms=$(stat -c%a "$file")
        owner=$(stat -c%U:%G "$file")
        echo "✅ $file"
        echo "   Taille: $size bytes | Perms: $perms | Owner: $owner"
    fi
done

echo ""
echo "🔍 CHEMINS RÉFÉRENCÉS DANS LES FICHIERS:"
echo "----------------------------------------"

# Compter les références
ui_refs=$(grep -r "ui/ui/images/logo.png" /var/www/phpnuxbill --include="*.html" --include="*.tpl" --include="*.php" 2>/dev/null | wc -l)
uploads_refs=$(grep -r "system/uploads/logo.png" /var/www/phpnuxbill --include="*.html" --include="*.tpl" --include="*.php" 2>/dev/null | wc -l)
upload_path_refs=$(grep -r "UPLOAD_PATH.*logo.png" /var/www/phpnuxbill --include="*.php" 2>/dev/null | wc -l)

echo "📊 Statistiques:"
echo "   - ui/ui/images/logo.png: $ui_refs références"
echo "   - system/uploads/logo.png: $uploads_refs références"  
echo "   - \$UPLOAD_PATH/logo.png: $upload_path_refs références (contrôleurs)"

echo ""
echo "⚠️  PROBLÈMES POTENTIELS:"
echo "----------------------------------------"

# Vérifier si les fichiers existent
if [ ! -f "/var/www/phpnuxbill/ui/ui/images/logo.png" ]; then
    echo "❌ /var/www/phpnuxbill/ui/ui/images/logo.png - MANQUANT"
else
    echo "✅ ui/ui/images/logo.png - OK"
fi

if [ ! -f "/var/www/phpnuxbill/system/uploads/logo.png" ]; then
    echo "❌ /var/www/phpnuxbill/system/uploads/logo.png - MANQUANT"
else
    echo "✅ system/uploads/logo.png - OK"
fi

# Vérifier les permissions web
if [ -f "/var/www/phpnuxbill/ui/ui/images/logo.png" ]; then
    owner=$(stat -c%U /var/www/phpnuxbill/ui/ui/images/logo.png)
    perms=$(stat -c%a /var/www/phpnuxbill/ui/ui/images/logo.png)
    if [ "$owner" != "www-data" ]; then
        echo "⚠️  ui/ui/images/logo.png: Propriétaire incorrect ($owner, devrait être www-data)"
    fi
fi

echo ""
echo "💡 RECOMMANDATIONS:"
echo "----------------------------------------"
echo "1. Vérifier que /var/www/phpnuxbill/ui/ui/images/logo.png existe"
echo "2. Vérifier que /var/www/phpnuxbill/system/uploads/logo.png existe"
echo "3. S'assurer que les permissions sont correctes (644 ou 664)"
echo "4. Tester l'accès via navigateur à: https://bill.zosoft.net/ui/ui/images/logo.png"

echo ""
echo "✅ Analyse terminée"
