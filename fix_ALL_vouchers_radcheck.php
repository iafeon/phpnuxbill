<?php
/**
 * Fix TOUS les Vouchers Utilisés - Version Complète
 * 
 * Crée Cleartext-Password dans radcheck pour TOUS les vouchers status=1
 * Peu importe le status de leur session (actif, expiré, etc.)
 */

include '/var/www/phpnuxbill/init.php';
require_once '/var/www/phpnuxbill/system/devices/Radius.php';

echo "=== Fix TOUS les Vouchers: Création Cleartext-Password ===\n\n";

// Récupérer TOUS les vouchers status=1 (utilisés)
$vouchers = ORM::for_table('tbl_voucher')
    ->where('routers', 'radius')
    ->where('status', 1)
    ->find_array();

echo "Total vouchers utilisés (status=1): " . count($vouchers) . "\n\n";

$radius = new Radius();
$fixed = 0;
$skipped = 0;

foreach ($vouchers as $v) {
    $code = $v['code'];

    // Vérifier si Cleartext-Password existe déjà dans radcheck
    $db_radius = ORM::get_db('radius');
    $radcheck_check = $db_radius->query("SELECT * FROM radcheck WHERE username='$code' AND attribute='Cleartext-Password'")->fetch();

    $has_password = ($radcheck_check !== false);

    if (!$has_password) {
        // Vérifier si voucher a une entrée dans tbl_user_recharges
        $tur = ORM::for_table('tbl_user_recharges')
            ->where('username', $code)
            ->find_one();

        if ($tur) {
            echo "Fixing voucher: $code (tbl_user_recharges status=" . $tur['status'] . ")\n";
        } else {
            echo "Fixing voucher: $code (PAS de tbl_user_recharges - voucher utilisé mais pas créé)\n";
        }

        // Créer Cleartext-Password dans radcheck
        // Pour les vouchers, password = code du voucher
        $radius->upsertCustomer($code, 'Cleartext-Password', $code);

        echo "  ✅ Created: Cleartext-Password = $code\n";
        $fixed++;
    } else {
        // Déjà OK, ne pas afficher pour éviter trop de spam
        $skipped++;
    }
}

echo "\n=== Résumé ===\n";
echo "Vouchers fixés: $fixed\n";
echo "Vouchers déjà OK: $skipped\n";
echo "Total: " . ($fixed + $skipped) . "\n";
echo "\n✅ Terminé!\n";
