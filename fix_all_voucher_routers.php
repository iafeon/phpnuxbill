<?php
/**
 * Script pour corriger TOUS les vouchers : routers doit être "radius" (nom du routeur)
 */

require_once 'init.php';

echo "=== CORRECTION DES VOUCHERS : ROUTERS → radius ===\n\n";

// Compter les vouchers qui n'ont pas "radius" comme routers
$toFix = ORM::for_table('tbl_voucher')
    ->where_not_equal('routers', 'radius')
    ->count();

echo "Vouchers à corriger: $toFix\n\n";

if ($toFix > 0) {
    echo "🔄 Mise à jour de tous les vouchers...\n";

    // Récupérer tous les vouchers à corriger
    $vouchers = ORM::for_table('tbl_voucher')
        ->where_not_equal('routers', 'radius')
        ->find_many();

    $updated = 0;
    foreach ($vouchers as $voucher) {
        $oldRouter = $voucher->routers;
        $voucher->routers = 'radius';
        $voucher->save();
        $updated++;

        if ($updated <= 5) {
            echo sprintf("  ✅ Voucher #%-4s: %-15s → radius\n", $voucher->id, $oldRouter);
        }
    }

    if ($updated > 5) {
        echo "  ... et " . ($updated - 5) . " autres\n";
    }

    echo "\n✅ $updated vouchers mis à jour avec succès !\n";
} else {
    echo "✅ Tous les vouchers sont déjà corrects (routers = radius)\n";
}

echo "\n=== Fin du script ===\n";
