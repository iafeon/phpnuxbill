<?php
/**
 * Script pour mettre à jour toutes les dates d'expiration au 31 Décembre 2025 23:59
 * et vérifier le renouvellement automatique
 */

require_once 'init.php';

echo "=== MISE À JOUR DES DATES D'EXPIRATION ===\n\n";

$targetDate = '2025-12-31';
$targetTime = '23:59:59';

// 1. Vérifier les abonnements actifs
$activeUsers = ORM::for_table('tbl_user_recharges')
    ->where('status', 'on')
    ->find_many();

echo "📊 Total d'abonnements actifs: " . count($activeUsers) . "\n\n";

// 2. Mettre à jour les dates
$updated = 0;
$alreadyCorrect = 0;

echo "🔄 Mise à jour des dates d'expiration...\n";
echo str_repeat("-", 80) . "\n";

foreach ($activeUsers as $user) {
    if ($user->expiration !== $targetDate || $user->time !== $targetTime) {
        $oldDate = $user->expiration . ' ' . $user->time;
        $user->expiration = $targetDate;
        $user->time = $targetTime;
        $user->save();

        echo sprintf(
            "✅ %-15s | %-20s | %s → %s %s\n",
            $user->username,
            $user->namebp,
            $oldDate,
            $targetDate,
            $targetTime
        );
        $updated++;
    } else {
        $alreadyCorrect++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RÉSUMÉ DE LA MISE À JOUR:\n";
echo str_repeat("=", 80) . "\n";
echo "✅ Mis à jour: $updated\n";
echo "✔️  Déjà correct: $alreadyCorrect\n";

// 3. Vérifier le renouvellement automatique
echo "\n" . str_repeat("=", 80) . "\n";
echo "🔄 VÉRIFICATION DU RENOUVELLEMENT AUTOMATIQUE:\n";
echo str_repeat("=", 80) . "\n";

$customers = ORM::for_table('tbl_customers')
    ->select('id')
    ->select('username')
    ->select('auto_renewal')
    ->find_many();

$autoRenewalOn = 0;
$autoRenewalOff = 0;

foreach ($customers as $customer) {
    if ($customer->auto_renewal == 1) {
        $autoRenewalOn++;
    } else {
        $autoRenewalOff++;
    }
}

echo "✅ Renouvellement automatique ACTIVÉ: $autoRenewalOn clients\n";
echo "❌ Renouvellement automatique DÉSACTIVÉ: $autoRenewalOff clients\n";

// 4. Vérifier la configuration globale
echo "\n📋 Configuration système:\n";
echo "  - Balance activé: " . ($config['enable_balance'] ?? 'non défini') . "\n";

echo "\n=== Fin du script ===\n";
