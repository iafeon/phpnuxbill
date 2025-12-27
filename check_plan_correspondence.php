<?php
/**
 * Script to check plan correspondence between active users and available plans
 */

require_once 'init.php';

echo "=== VÉRIFICATION DE LA CORRESPONDANCE DES PLANS ===\n\n";

// Get all active plans
$activePlans = ORM::for_table('tbl_plans')
    ->where('enabled', 1)
    ->find_many();

echo "📋 PLANS DISPONIBLES (actifs):\n";
echo str_repeat("-", 60) . "\n";
$planNames = [];
foreach ($activePlans as $plan) {
    echo sprintf(
        "  ID: %-3s | Nom: %-30s | Type: %s\n",
        $plan->id,
        $plan->name_plan,
        $plan->typebp
    );
    $planNames[] = $plan->name_plan;
}
echo "\nTotal: " . count($activePlans) . " plans actifs\n\n";

// Get all users with active recharges and their plans
$activeUsers = ORM::for_table('tbl_user_recharges')
    ->where('status', 'on')
    ->select('namebp')
    ->select_expr('COUNT(*)', 'count')
    ->group_by('namebp')
    ->order_by_desc('count')
    ->find_many();

echo "👥 PLANS UTILISÉS PAR LES UTILISATEURS ACTIFS:\n";
echo str_repeat("-", 60) . "\n";

$totalActiveUsers = 0;
$planIssues = [];

foreach ($activeUsers as $user) {
    $planName = $user->namebp;
    $count = $user->count;
    $totalActiveUsers += $count;

    // Check if plan exists
    $planExists = in_array($planName, $planNames);
    $status = $planExists ? "✅ OK" : "❌ PLAN MANQUANT";

    echo sprintf(
        "  %-30s | %3d utilisateurs | %s\n",
        $planName,
        $count,
        $status
    );

    if (!$planExists) {
        $planIssues[] = [
            'plan' => $planName,
            'users' => $count
        ];
    }
}

echo "\nTotal: " . $totalActiveUsers . " utilisateurs actifs\n\n";

// Summary
echo str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ DE LA VÉRIFICATION:\n";
echo str_repeat("=", 60) . "\n";

if (count($planIssues) == 0) {
    echo "✅ TOUT EST OK ! Tous les plans utilisés existent dans le système.\n";
} else {
    echo "⚠️  PROBLÈMES DÉTECTÉS !\n\n";
    echo "Plans manquants ou désactivés utilisés par des utilisateurs:\n";
    foreach ($planIssues as $issue) {
        echo sprintf(
            "  - %-30s: %d utilisateurs affectés\n",
            $issue['plan'],
            $issue['users']
        );
    }
    echo "\n";
    echo "RECOMMANDATION:\n";
    echo "1. Réactiver ces plans dans le système OU\n";
    echo "2. Migrer les utilisateurs vers d'autres plans actifs\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Check for orphaned vouchers
echo "\n🎫 VÉRIFICATION DES VOUCHERS:\n";
echo str_repeat("-", 60) . "\n";

$totalVouchers = ORM::for_table('tbl_voucher')
    ->count();

$vouchersByPlan = ORM::for_table('tbl_voucher')
    ->select('routers')
    ->select_expr('COUNT(*)', 'count')
    ->group_by('routers')
    ->order_by_desc('count')
    ->find_many();

echo "Vouchers par plan:\n";
foreach ($vouchersByPlan as $v) {
    $planName = $v->routers;
    $count = $v->count;
    $planExists = in_array($planName, $planNames);
    $status = $planExists ? "✅" : "❌";

    echo sprintf("  %s %-30s: %d vouchers\n", $status, $planName, $count);
}

echo "\nTotal de vouchers: " . $totalVouchers . "\n";

echo "\n✅ Vérification terminée.\n";
