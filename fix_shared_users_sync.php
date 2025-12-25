<?php
/**
 * Script to re-sync ALL active users and insert missing Simultaneous-Use and Port-Limit attributes
 * This fixes the Shared Users feature
 */

include "init.php";

// Load Radius device class
require_once 'system/devices/Radius.php';

echo "=== SHARED USERS FIX - RE-SYNC SCRIPT ===\n\n";

// Get all active users
$activeUsers = ORM::for_table('tbl_user_recharges')
    ->where('status', 'on')
    ->where('routers', 'radius')
    ->find_array();

echo "Found " . count($activeUsers) . " active RADIUS users\n\n";

$fixed = 0;
$errors = 0;

foreach ($activeUsers as $userRecharge) {
    $username = $userRecharge['username'];

    // Get customer info
    $customer = ORM::for_table('tbl_customers')
        ->where('username', $username)
        ->find_one();

    if (!$customer) {
        echo "⚠️  Customer not found: $username\n";
        $errors++;
        continue;
    }

    // Get plan info
    $plan = ORM::for_table('tbl_plans')
        ->where('id', $userRecharge['plan_id'])
        ->find_one();

    if (!$plan) {
        echo "⚠️  Plan not found for: $username\n";
        $errors++;
        continue;
    }

    echo "Processing: $username (Plan: {$plan['name_plan']}, Type: {$plan['type']}, Shared: {$plan['shared_users']})\n";

    // Use Radius device to sync
    $radius = new Radius();

    // Force re-sync which will call customerAddPlan -> customerUpsert -> upsertCustomer
    try {
        $radius->sync_customer($customer->as_array(), $plan->as_array());

        // Verify attributes were inserted
        $simUse = ORM::for_table('radcheck', 'radius')
            ->where('username', $username)
            ->where('attribute', 'Simultaneous-Use')
            ->find_one();

        $portLimit = ORM::for_table('radcheck', 'radius')
            ->where('username', $username)
            ->where('attribute', 'Port-Limit')
            ->find_one();

        if ($simUse && $portLimit) {
            echo "  ✅ Simultaneous-Use: {$simUse['value']}\n";
            echo "  ✅ Port-Limit: {$portLimit['value']}\n";
            $fixed++;
        } else {
            echo "  ⚠️  Attributes still missing after sync\n";
            $errors++;
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $errors++;
    }

    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total users: " . count($activeUsers) . "\n";
echo "Successfully fixed: $fixed\n";
echo "Errors: $errors\n";
echo "\n=== DONE ===\n";
