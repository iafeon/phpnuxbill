<?php
/**
 * Script pour corriger le dernier voucher avec le nom de plan 'radius'
 */

require_once 'init.php';

echo "=== CORRECTION DU VOUCHER 'radius' ===\n\n";

// Rechercher le voucher avec routers = 'radius'  
$voucher = ORM::for_table('tbl_voucher')
    ->where('routers', 'radius')
    ->find_one();

if ($voucher) {
    echo "✅ Voucher trouvé:\n";
    echo "  ID: " . $voucher->id . "\n";
    echo "  Code: " . $voucher->code . "\n";
    echo "  Plan actuel (routers): " . $voucher->routers . "\n";
    echo "  Créé le: " . $voucher->created_at . "\n";
    echo "  Statut: " . ($voucher->status == 0 ? "Non utilisé" : "Utilisé") . "\n\n";

    echo "🔄 Changement du plan de 'radius' vers 'Premium'...\n";
    $voucher->routers = 'Premium';
    $voucher->save();
    echo "✅ Voucher mis à jour avec succès !\n";
    echo "  Nouveau plan: Premium\n";
} else {
    echo "ℹ️  Aucun voucher avec le plan 'radius' trouvé.\n";
    echo "   Tous les vouchers sont déjà corrects !\n";
}

echo "\n=== Fin du script ===\n";
