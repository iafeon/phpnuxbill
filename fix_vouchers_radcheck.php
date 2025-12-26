<?php
/**
 * Fix Vouchers - Ajouter Cleartext-Password dans radcheck
 * 
 * Problème: Les vouchers actifs n'ont pas d'entrée Cleartext-Password dans radcheck
 * Solution: Créer les entrées manquantes pour tous les vouchers actifs
 */

include '/var/www/phpnuxbill/init.php';
require_once '/var/www/phpnuxbill/system/devices/Radius.php';

echo "=== Fix Vouchers: Création Cleartext-Password dans radcheck ===\n\n";

// Récupérer tous les vouchers utilisés (status=1) qui ont une session active
$vouchers = ORM::for_table('tbl_voucher')
    ->where('routers', 'radius')
    ->where('status', 1)  // Déjà activés
    ->find_array();

echo "Vouchers trouvés dans tbl_voucher: " . count($vouchers) . "\n\n";

$radius = new Radius();
$fixed = 0;
$skipped = 0;

foreach ($vouchers as $v) {
    $code = $v['code'];

    // Vérifier si le voucher est actif dans tbl_user_recharges
    $tur = ORM::for_table('tbl_user_recharges')
        ->where('username', $code)
        ->where('status', 'on')
        ->find_one();

    if ($tur) {
        // Vérifier si Cleartext-Password existe déjà dans radcheck
        $db_radius = ORM::get_db('radius');
        $radcheck_check = $db_radius->query("SELECT * FROM radcheck WHERE username='$code' AND attribute='Cleartext-Password'")->fetch();

        $has_password = ($radcheck_check !== false);

        if (!$has_password) {
            echo "Fixing voucher: $code\n";

            // Créer Cleartext-Password dans radcheck
            // Pour les vouchers, password = code du voucher
            $radius->upsertCustomer($code, 'Cleartext-Password', $code);

            echo "  ✅ Created: Cleartext-Password = $code\n";
            $fixed++;
        } else {
            echo "Skipping $code (already has Cleartext-Password)\n";
            $skipped++;
        }
    }
}

echo "\n=== Résumé ===\n";
echo "Vouchers fixés: $fixed\n";
echo "Vouchers déjà OK: $skipped\n";
echo "\n✅ Terminé!\n";
