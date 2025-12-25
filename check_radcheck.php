<?php
/**
 * Script to verify radcheck attributes for Shared Users
 */

include "init.php";

echo "=== RADCHECK VERIFICATION ===\n\n";

// Get some test users
$users = ORM::for_table('tbl_user_recharges', 'default')
    ->where('status', 'on')
    ->limit(5)
    ->find_array();

echo "Active Users Found: " . count($users) . "\n\n";

foreach ($users as $user) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Username: " . $user['username'] . "\n";
    
    // Get plan info
    $plan = ORM::for_table('tbl_plans', 'default')
        ->where('id', $user['plan_id'])
        ->find_one();
    
    if ($plan) {
        echo "Plan: " . $plan['name_plan'] . "\n";
        echo "Plan Type: " . $plan['type'] . "\n";
        echo "Shared Users (Plan): " . $plan['shared_users'] . "\n\n";
    }
    
    // Get radcheck attributes
    $attrs = ORM::for_table('radcheck', 'radius')
        ->where('username', $user['username'])
        ->find_array();
    
    echo "Attributes in radcheck:\n";
    if (count($attrs) > 0) {
        foreach ($attrs as $attr) {
            echo "  - " . $attr['attribute'] . " " . $attr['op'] . " " . $attr['value'] . "\n";
        }
    } else {
        echo "  ⚠️  NO ATTRIBUTES FOUND!\n";
    }
    
    // Check for specific attributes
    $hasSimultaneous = false;
    $hasPortLimit = false;
    foreach ($attrs as $attr) {
        if ($attr['attribute'] == 'Simultaneous-Use') $hasSimultaneous = true;
        if ($attr['attribute'] == 'Port-Limit') $hasPortLimit = true;
    }
    
    echo "\n✓ Status:\n";
    echo "  Simultaneous-Use: " . ($hasSimultaneous ? "✅ PRESENT" : "❌ MISSING") . "\n";
    echo "  Port-Limit: " . ($hasPortLimit ? "✅ PRESENT" : "❌ MISSING") . "\n";
    echo "\n";
}

echo "=== END VERIFICATION ===\n";
