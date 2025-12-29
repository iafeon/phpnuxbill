<?php
/**
 * Script pour effacer tous les historiques d'activation dans PHPNuxBill
 * ATTENTION : Cette opération est IRRÉVERSIBLE !
 */

require_once 'init.php';

echo "=== NETTOYAGE DES HISTORIQUES D'ACTIVATION ===\n\n";

// 1. Compter les données à supprimer
$inactiveRecharges = ORM::for_table('tbl_user_recharges')
    ->where('status', 'off')
    ->count();

$logs = ORM::for_table('tbl_logs')->count();

$radacctCount = 0;
try {
    $radacctCount = ORM::for_table('radacct')->count();
} catch (Exception $e) {
    // Table RADIUS non accessible
}

echo "📊 DONNÉES À SUPPRIMER:\n";
echo str_repeat("=", 60) . "\n";
echo "Abonnements inactifs/expirés: $inactiveRecharges\n";
echo "Logs système: $logs\n";
if ($radacctCount > 0) {
    echo "Sessions RADIUS: $radacctCount\n";
}

echo "\n⚠️  ATTENTION : Cette opération est IRRÉVERSIBLE !\n";
echo "Les abonnements ACTIFS seront CONSERVÉS.\n\n";

echo "Voulez-vous continuer ? (o/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'o') {
    echo "❌ Opération annulée.\n";
    exit(0);
}

echo "\n🔄 Suppression en cours...\n";
echo str_repeat("-", 60) . "\n";

// 2. Supprimer les abonnements inactifs
if ($inactiveRecharges > 0) {
    ORM::for_table('tbl_user_recharges')
        ->where('status', 'off')
        ->delete_many();
    echo "✅ $inactiveRecharges abonnements inactifs supprimés\n";
}

// 3. Supprimer les logs
if ($logs > 0) {
    ORM::for_table('tbl_logs')->delete_many();
    echo "✅ $logs logs supprimés\n";
}

// 4. Supprimer les sessions RADIUS
if ($radacctCount > 0) {
    try {
        ORM::for_table('radacct')->delete_many();
        echo "✅ $radacctCount sessions RADIUS supprimées\n";
    } catch (Exception $e) {
        echo "⚠️  Sessions RADIUS non supprimées (table non accessible)\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ FINAL:\n";
echo str_repeat("=", 60) . "\n";

$remainingActive = ORM::for_table('tbl_user_recharges')
    ->where('status', 'on')
    ->count();

$remainingInactive = ORM::for_table('tbl_user_recharges')
    ->where('status', 'off')
    ->count();

$remainingLogs = ORM::for_table('tbl_logs')->count();

echo "Abonnements actifs conservés: $remainingActive\n";
echo "Abonnements inactifs restants: $remainingInactive\n";
echo "Logs restants: $remainingLogs\n";

echo "\n✅ Nettoyage terminé !\n";
echo "=== Fin du script ===\n";
