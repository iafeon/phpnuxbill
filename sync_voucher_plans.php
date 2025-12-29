<?php
/**
 * Script pour synchroniser le champ 'routers' avec le vrai nom du plan basé sur 'id_plan'
 */

require_once 'init.php';

echo "=== SYNCHRONISATION DES NOMS DE PLANS DANS LES VOUCHERS ===\n\n";

// Récupérer tous les vouchers
$vouchers = ORM::for_table('tbl_voucher')->find_many();
$totalVouchers = count($vouchers);
$updated = 0;
$errors = 0;

echo "Total de vouchers à vérifier: $totalVouchers\n\n";

foreach ($vouchers as $voucher) {
    // Récupérer le vrai plan basé sur id_plan
    $plan = ORM::for_table('tbl_plans')->find_one($voucher->id_plan);

    if ($plan) {
        $correctName = $plan->name_plan;
        $currentName = $voucher->routers;

        if ($currentName !== $correctName) {
            echo sprintf(
                "🔄 Voucher #%-4s (%-10s): %-15s → %-15s\n",
                $voucher->id,
                $voucher->code,
                $currentName,
                $correctName
            );
            $voucher->routers = $correctName;
            $voucher->save();
            $updated++;
        }
    } else {
        echo sprintf(
            "❌ Voucher #%-4s (%-10s): Plan ID %s introuvable !\n",
            $voucher->id,
            $voucher->code,
            $voucher->id_plan
        );
        $errors++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Vouchers mis à jour: $updated\n";
echo "❌ Erreurs (plan introuvable): $errors\n";
echo "✔️  Vouchers déjà corrects: " . ($totalVouchers - $updated - $errors) . "\n";
echo "\n=== Fin du script ===\n";
