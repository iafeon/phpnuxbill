<?php
/**
 * Script pour migrer Simultaneous-Use et Port-Limit de radcheck vers radreply
 * ET re-synchroniser tous les utilisateurs actifs
 */

include "init.php";

echo "=== MIGRATION SIMULTANEOUS-USE ET PORT-LIMIT ===\n\n";

// Étape 1: Copier les attributs de radcheck vers radreply
echo "Étape 1: Migration radcheck → radreply\n";
echo "-----------------------------------------\n";

$attrs = ORM::for_table('radcheck', 'radius')
    ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
    ->find_array();

$migrated = 0;
foreach ($attrs as $attr) {
    // Vérifier si existe déjà dans radreply
    $existing = ORM::for_table('radreply', 'radius')
        ->where('username', $attr['username'])
        ->where('attribute', $attr['attribute'])
        ->find_one();

    if ($existing) {
        // Mettre à jour
        $existing->value = $attr['value'];
        $existing->op = ':=';
        $existing->save();
        echo "  ✓ Mis à jour: " . $attr['username'] . " - " . $attr['attribute'] . " = " . $attr['value'] . "\n";
    } else {
        // Créer nouveau
        $new = ORM::for_table('radreply', 'radius')->create();
        $new->username = $attr['username'];
        $new->attribute = $attr['attribute'];
        $new->op = ':=';
        $new->value = $attr['value'];
        $new->save();
        echo "  ✓ Créé: " . $attr['username'] . " - " . $attr['attribute'] . " = " . $attr['value'] . "\n";
    }
    $migrated++;
}

echo "\nTotal migré: $migrated attributs\n\n";

// Étape 2: Supprimer de radcheck (optionnel - on garde pour l'instant)
echo "Étape 2: Nettoyage radcheck\n";
echo "----------------------------\n";

$deletedCheck = ORM::for_table('radcheck', 'radius')
    ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
    ->delete_many();

echo "✓ Supprimé $deletedCheck entrées de radcheck\n\n";

// Étape 3: Re-synchroniser TOUS les utilisateurs actifs
echo "Étape 3: Re-synchronisation utilisateurs actifs\n";
echo "------------------------------------------------\n";

$activeUsers = ORM::for_table('tbl_user_recharges', 'default')
    ->where('status', 'on')
    ->where('routers', 'radius')
    ->find_array();

echo "Utilisateurs actifs à synchroniser: " . count($activeUsers) . "\n\n";

// Load Radius class
require_once 'system/devices/Radius.php';
$radius = new Radius();
$synced = 0;

foreach ($activeUsers as $recharge) {
    // Get customer
    $customer = ORM::for_table('tbl_customers', 'default')
        ->where('username', $recharge['username'])
        ->find_one();

    if (!$customer) {
        echo "  ⚠️  Customer non trouvé: " . $recharge['username'] . "\n";
        continue;
    }

    // Get plan
    $plan = ORM::for_table('tbl_plans', 'default')
        ->where('id', $recharge['plan_id'])
        ->find_one();

    if (!$plan) {
        echo "  ⚠️  Plan non trouvé pour: " . $recharge['username'] . "\n";
        continue;
    }

    // Synchroniser
    try {
        $radius->sync_customer($customer, $plan);
        echo "  ✓ Synchronisé: " . $customer['username'] . " (Plan: " . $plan['name_plan'] . ", Shared: " . $plan['shared_users'] . ")\n";
        $synced++;
    } catch (Exception $e) {
        echo "  ❌ Erreur pour " . $customer['username'] . ": " . $e->getMessage() . "\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "Attributs migrés: $migrated\n";
echo "Attributs supprimés de radcheck: $deletedCheck\n";
echo "Utilisateurs synchronisés: $synced / " . count($activeUsers) . "\n\n";

echo "✅ Migration terminée!\n\n";

// Vérification finale
echo "=== VÉRIFICATION FINALE ===\n";
echo "Exemple d'utilisateurs (premiers 3):\n\n";

$examples = ORM::for_table('tbl_user_recharges', 'default')
    ->where('status', 'on')
    ->limit(3)
    ->find_array();

foreach ($examples as $ex) {
    echo "Username: " . $ex['username'] . "\n";

    // radcheck
    $check = ORM::for_table('radcheck', 'radius')
        ->where('username', $ex['username'])
        ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
        ->count();
    echo "  radcheck: $check attributs (devrait être 0)\n";

    // radreply
    $reply = ORM::for_table('radreply', 'radius')
        ->where('username', $ex['username'])
        ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
        ->find_array();
    echo "  radreply: " . count($reply) . " attributs (devrait être 2)\n";
    foreach ($reply as $r) {
        echo "    - " . $r['attribute'] . " := " . $r['value'] . "\n";
    }
    echo "\n";
}

echo "=== FIN ===\n";
