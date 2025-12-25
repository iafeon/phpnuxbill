<?php
/**
 * Script pour nettoyer les attributs invalides de radcheck
 */

include "init.php";

echo "=== NETTOYAGE radcheck: Suppression attributs non-standards ===\n\n";

// Attributs à supprimer de radcheck (ne sont pas des attributs RADIUS check valides)
$invalid_attrs = [
    'Max-All-Session',
    'Expiration',
    'WISPr-Session-Terminate-Time',
    'Mikrotik-Wireless-Comment'
];

foreach ($invalid_attrs as $attr) {
    $count = ORM::for_table('radcheck', 'radius')
        ->where('attribute', $attr)
        ->delete_many();
    
    echo "Supprimé '$attr': $count entrées\n";
}

echo "\n✅ Nettoyage terminé!\n";
echo "\nAttributs restants dans radcheck:\n";
$remaining = ORM::for_table('radcheck', 'radius')
    ->select('attribute')
    ->distinct()
    ->find_array();

foreach ($remaining as $r) {
    echo "  - " . $r['attribute'] . "\n";
}
