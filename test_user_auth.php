<?php
/**
 * Script pour tester l'authentification d'un utilisateur spécifique
 */

include "init.php";

$username = $argv[1] ?? 'lebazol';

echo "=== TEST AUTHENTIFICATION: $username ===\n\n";

// Vérifier dans tbl_user_recharges
$tur = ORM::for_table('tbl_user_recharges', 'default')
    ->where('username', $username)
    ->find_one();

if ($tur) {
    echo "✓ User trouvé dans tbl_user_recharges:\n";
    echo "  - Username: " . $tur['username'] . "\n";
    echo "  - Status: " . $tur['status'] . "\n";
    echo "  - Expiration: " . $tur['expiration'] . " " . $tur['time'] . "\n";
    echo "  - Plan ID: " . $tur['plan_id'] . "\n\n";

    // Get plan
    $plan = ORM::for_table('tbl_plans', 'default')
        ->where('id', $tur['plan_id'])
        ->find_one();

    if ($plan) {
        echo "✓ Plan:\n";
        echo "  - Name: " . $plan['name_plan'] . "\n";
        echo "  - Type: " . $plan['type'] . "\n";
        echo "  - Shared Users: " . $plan['shared_users'] . "\n\n";
    }
} else {
    echo "❌ User NOT found in tbl_user_recharges\n\n";
}

// Vérifier dans tbl_customers
$customer = ORM::for_table('tbl_customers', 'default')
    ->where('username', $username)
    ->find_one();

if ($customer) {
    echo "✓ Customer trouvé:\n";
    echo "  - Username: " . $customer['username'] . "\n";
    echo "  - Status: " . $customer['status'] . "\n";
    echo "  - Password: " . $customer['password'] . "\n\n";
} else {
    echo "❌ Customer NOT found\n\n";
}

// Vérifier dans radcheck
$radcheck = ORM::for_table('radcheck', 'radius')
    ->where('username', $username)
    ->find_array();

echo "Attributes in radcheck (" . count($radcheck) . " total):\n";
if (count($radcheck) > 0) {
    foreach ($radcheck as $attr) {
        echo "  - " . $attr['attribute'] . " " . $attr['op'] . " " . $attr['value'] . "\n";
    }
} else {
    echo "  ❌ NO ATTRIBUTES!\n";
}
echo "\n";

// Vérifier sessions actives
$sessions = ORM::for_table('radacct', 'radius')
    ->where('username', $username)
    ->where_raw('acctstoptime IS NULL')
    ->find_array();

echo "Sessions actives: " . count($sessions) . "\n";
if (count($sessions) > 0) {
    foreach ($sessions as $s) {
        echo "  - IP: " . $s['framedipaddress'] . " | Start: " . $s['acctstarttime'] . "\n";
    }
}

echo "\n=== END TEST ===\n";
