<?php
/**
 * Script de Rollback - Annule la Migration des Plans
 */

require_once 'init.php';

echo "=== ROLLBACK DE LA MIGRATION DES PLANS ===\n\n";

// Lister les backups disponibles
$backupDir = 'system/backups';
$backups = glob("$backupDir/plan_migration_*.json");

if (empty($backups)) {
    echo "❌ Aucun backup trouvé.\n";
    exit(1);
}

echo "📋 BACKUPS DISPONIBLES:\n";
echo str_repeat("-", 70) . "\n";
foreach ($backups as $index => $backup) {
    echo "  [" . ($index + 1) . "] " . basename($backup) . "\n";
}

echo "\nUtilisez le dernier backup (le plus récent) ? [O/n]: ";
$handle = fopen("php://stdin", "r");
$response = trim(fgets($handle));

if (strtolower($response) === 'n') {
    echo "Rollback annulé.\n";
    exit(0);
}

// Utiliser le dernier backup
rsort($backups);
$backupFile = $backups[0];

echo "\n💾 Chargement du backup: " . basename($backupFile) . "\n";
$backup = json_decode(file_get_contents($backupFile), true);

echo "  Timestamp: {$backup['timestamp']}\n";
echo "  Recharges: " . count($backup['user_recharges']) . "\n";
echo "  Vouchers: " . count($backup['vouchers']) . "\n\n";

echo "⚠️  ATTENTION: Cette opération va restaurer les données.\n";
echo "Continuer ? [O/n]: ";
$response = trim(fgets($handle));

if (strtolower($response) === 'n') {
    echo "Rollback annulé.\n";
    exit(0);
}

echo "\n🔄 RESTAURATION EN COURS:\n";
echo str_repeat("-", 70) . "\n";

$stats = ['user_recharges' => 0, 'vouchers' => 0];

// Restaurer user_recharges
foreach ($backup['user_recharges'] as $data) {
    $record = ORM::for_table('tbl_user_recharges')->find_one($data['id']);
    if ($record) {
        $record->namebp = $data['namebp'];
        $record->save();
        $stats['user_recharges']++;
    }
}

// Restaurer vouchers
foreach ($backup['vouchers'] as $data) {
    $record = ORM::for_table('tbl_voucher')->find_one($data['id']);
    if ($record) {
        $record->routers = $data['routers'];
        $record->save();
        $stats['vouchers']++;
    }
}

echo "✅ Restauration terminée:\n";
echo "  - User recharges: {$stats['user_recharges']}\n";
echo "  - Vouchers: {$stats['vouchers']}\n";
