<?php
/**
 * Script pour annuler la migration et restaurer les attributs dans radcheck
 * SI NÉCESSAIRE
 */

include "init.php";

echo "=== ROLLBACK: Restaurer Simultaneous-Use et Port-Limit dans radcheck ===\n\n";

// Copier de radreply vers radcheck
$attrs = ORM::for_table('radreply', 'radius')
    ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
    ->find_array();

echo "Attributs trouvés dans radreply: " . count($attrs) . "\n\n";

$restored = 0;
foreach ($attrs as $attr) {
    // Vérifier si existe déjà dans radcheck
    $existing = ORM::for_table('radcheck', 'radius')
        ->where('username', $attr['username'])
        ->where('attribute', $attr['attribute'])
        ->find_one();

    if ($existing) {
        echo "  ⚠️  Déjà présent dans radcheck: " . $attr['username'] . " - " . $attr['attribute'] . "\n";
    } else {
        // Créer dans radcheck
        $new = ORM::for_table('radcheck', 'radius')->create();
        $new->username = $attr['username'];
        $new->attribute = $attr['attribute'];
        $new->op = ':=';
        $new->value = $attr['value'];
        $new->save();
        echo "  ✓ Restauré: " . $attr['username'] . " - " . $attr['attribute'] . " = " . $attr['value'] . "\n";
        $restored++;
    }
}

echo "\nTotal restauré: $restored attributs\n\n";

// Supprimer de radreply
$deleted = ORM::for_table('radreply', 'radius')
    ->where_in('attribute', ['Simultaneous-Use', 'Port-Limit'])
    ->delete_many();

echo "Supprimé de radreply: $deleted attributs\n\n";

echo "✅ Rollback terminé!\n";
