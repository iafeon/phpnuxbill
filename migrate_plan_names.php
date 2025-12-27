<?php
/**
 * Script de Migration des Noms de Plans
 * Option 2: Met à jour les noms de plans dans la base de données
 */

require_once 'init.php';

echo "=== MIGRATION DES NOMS DE PLANS ===\n\n";

// Mapping des anciens noms vers les nouveaux noms
$planMapping = [
    'basic_reseller' => 'Basic',
    'premium' => 'Premium',
    'premium_no_tv' => 'Premium no TV',
    'premium_plus' => 'Premium Plus',
    'collab' => 'Collab',
    'standard_reseller' => 'Standard',
    // Vouchers
    'radius' => 'Premium',  // À ajuster selon vos besoins
    'Zosoft-Mikrotik' => 'Premium',  // À ajuster selon vos besoins
];

echo "📋 MAPPING DES PLANS:\n";
echo str_repeat("-", 70) . "\n";
foreach ($planMapping as $old => $new) {
    echo sprintf("  %-25s → %s\n", $old, $new);
}
echo "\n";

// Vérifier que les nouveaux plans existent
echo "🔍 VÉRIFICATION DES PLANS CIBLES:\n";
echo str_repeat("-", 70) . "\n";
$missingPlans = [];
foreach (array_unique(array_values($planMapping)) as $newPlan) {
    $exists = ORM::for_table('tbl_plans')
        ->where('name_plan', $newPlan)
        ->where('enabled', 1)
        ->find_one();

    if ($exists) {
        echo "  ✅ $newPlan (ID: {$exists->id})\n";
    } else {
        echo "  ❌ $newPlan - PLAN N'EXISTE PAS!\n";
        $missingPlans[] = $newPlan;
    }
}

if (count($missingPlans) > 0) {
    echo "\n⛔ ERREUR: Certains plans cibles n'existent pas.\n";
    echo "Veuillez vérifier le mapping avant de continuer.\n";
    exit(1);
}

echo "\n✅ Tous les plans cibles existent.\n\n";

// 1. BACKUP DES DONNÉES
echo "💾 CRÉATION DU BACKUP:\n";
echo str_repeat("-", 70) . "\n";

$backupDir = 'system/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Y-m-d_H-i-s');
$backupFile = "$backupDir/plan_migration_backup_$timestamp.sql";

// Backup tbl_user_recharges
$userRecharges = ORM::for_table('tbl_user_recharges')->find_many();
$voucherData = ORM::for_table('tbl_voucher')->find_many();

echo "  Sauvegarde de " . count($userRecharges) . " recharges utilisateurs...\n";
echo "  Sauvegarde de " . count($voucherData) . " vouchers...\n";

// Créer un backup JSON
$backup = [
    'timestamp' => $timestamp,
    'user_recharges' => [],
    'vouchers' => []
];

foreach ($userRecharges as $ur) {
    $backup['user_recharges'][] = [
        'id' => $ur->id,
        'username' => $ur->username,
        'namebp' => $ur->namebp,
        'status' => $ur->status
    ];
}

foreach ($voucherData as $v) {
    $backup['vouchers'][] = [
        'id' => $v->id,
        'code' => $v->code,
        'routers' => $v->routers
    ];
}

file_put_contents("$backupDir/plan_migration_$timestamp.json", json_encode($backup, JSON_PRETTY_PRINT));
echo "  ✅ Backup créé: $backupDir/plan_migration_$timestamp.json\n\n";

// 2. MIGRATION DES DONNÉES
echo "🔄 MIGRATION EN COURS:\n";
echo str_repeat("-", 70) . "\n";

$stats = [
    'user_recharges_updated' => 0,
    'vouchers_updated' => 0
];

// Migrer tbl_user_recharges
echo "  Mise à jour de tbl_user_recharges...\n";
foreach ($planMapping as $oldName => $newName) {
    $updated = ORM::for_table('tbl_user_recharges')
        ->where('namebp', $oldName)
        ->find_many();

    $count = 0;
    foreach ($updated as $record) {
        $record->namebp = $newName;
        $record->save();
        $count++;
    }

    if ($count > 0) {
        echo "    ✅ $oldName → $newName: $count enregistrements\n";
        $stats['user_recharges_updated'] += $count;
    }
}

// Migrer tbl_voucher
echo "\n  Mise à jour de tbl_voucher...\n";
foreach ($planMapping as $oldName => $newName) {
    $updated = ORM::for_table('tbl_voucher')
        ->where('routers', $oldName)
        ->find_many();

    $count = 0;
    foreach ($updated as $record) {
        $record->routers = $newName;
        $record->save();
        $count++;
    }

    if ($count > 0) {
        echo "    ✅ $oldName → $newName: $count vouchers\n";
        $stats['vouchers_updated'] += $count;
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RÉSULTATS DE LA MIGRATION:\n";
echo str_repeat("=", 70) . "\n";
echo "  Recharges utilisateurs mises à jour: {$stats['user_recharges_updated']}\n";
echo "  Vouchers mis à jour: {$stats['vouchers_updated']}\n";
echo "\n✅ Migration terminée avec succès!\n\n";

echo "📝 FICHIERS DE BACKUP:\n";
echo "  - $backupDir/plan_migration_$timestamp.json\n\n";

echo "⚠️  Pour annuler la migration, utilisez le script de rollback.\n";
echo "✅ Vérification recommandée avec: php check_plan_correspondence.php\n";
