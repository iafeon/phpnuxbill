<?php
/**
 * Test du fonctionnement Shared Users
 * Vérifie que les limites sont correctement appliquées
 */

include "init.php";

echo "=== TEST FONCTIONNEMENT SHARED USERS ===\n\n";

// Liste des utilisateurs test
$testUsers = ['tsiory', 'lebazol', 'tech', 'patric'];

foreach ($testUsers as $username) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 UTILISATEUR: $username\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 1. Récupérer le plan
    $user = ORM::for_table('tbl_user_recharges')
        ->where('username', $username)
        ->where('status', 'on')
        ->find_one();

    if (!$user) {
        echo "⚠️  Utilisateur non trouvé ou inactif\n\n";
        continue;
    }

    $plan = ORM::for_table('tbl_plans')
        ->where('id', $user['plan_id'])
        ->find_one();

    echo "📋 INFORMATIONS PLAN\n";
    echo "   Nom: {$plan['name_plan']}\n";
    echo "   Type: {$plan['type']}\n";
    echo "   Shared Users (limite): {$plan['shared_users']}\n";
    echo "   Expiration: {$user['expiration']} {$user['time']}\n\n";

    // 2. Vérifier attributs RADIUS dans radcheck
    echo "🔍 ATTRIBUTS RADIUS (radcheck)\n";

    $simUse = ORM::for_table('radcheck', 'radius')
        ->where('username', $username)
        ->where('attribute', 'Simultaneous-Use')
        ->find_one();

    $portLimit = ORM::for_table('radcheck', 'radius')
        ->where('username', $username)
        ->where('attribute', 'Port-Limit')
        ->find_one();

    if ($simUse) {
        echo "   ✅ Simultaneous-Use: {$simUse['value']}\n";
    } else {
        echo "   ❌ Simultaneous-Use: MANQUANT\n";
    }

    if ($portLimit) {
        echo "   ✅ Port-Limit: {$portLimit['value']}\n";
    } else {
        echo "   ❌ Port-Limit: MANQUANT\n";
    }

    // 3. Compter sessions actives
    $sessions = ORM::for_table('radacct', 'radius')
        ->where('username', $username)
        ->where_raw('acctstoptime IS NULL')
        ->find_array();

    $sessionCount = count($sessions);

    echo "\n📊 SESSIONS ACTIVES\n";
    echo "   Nombre: $sessionCount\n";

    if ($sessionCount > 0) {
        echo "   Détails:\n";
        foreach ($sessions as $sess) {
            echo "      - IP: {$sess['framedipaddress']} | Démarrée: {$sess['acctstarttime']}\n";
        }
    }

    // 4. Statut limite
    echo "\n🎯 STATUT LIMITE\n";
    $limit = $simUse ? intval($simUse['value']) : 0;
    $available = $limit - $sessionCount;

    if ($limit > 0) {
        echo "   Limite configurée: $limit sessions\n";
        echo "   Sessions actives: $sessionCount\n";
        echo "   Sessions disponibles: $available\n";

        if ($sessionCount < $limit) {
            echo "   État: ✅ CONNEXIONS POSSIBLES ($available restantes)\n";
        } else if ($sessionCount == $limit) {
            echo "   État: ⚠️  LIMITE ATTEINTE (aucune nouvelle session acceptée)\n";
        } else {
            echo "   État: ❌ LIMITE DÉPASSÉE (anormal !)\n";
        }
    } else {
        echo "   ❌ Pas de limite configurée (attributs manquants)\n";
    }

    // 5. Test de conformité
    echo "\n✓ CONFORMITÉ\n";
    $conformite = true;

    if (!$simUse || !$portLimit) {
        echo "   ❌ Attributs RADIUS manquants\n";
        $conformite = false;
    } else if (intval($simUse['value']) != $plan['shared_users']) {
        echo "   ❌ Simultaneous-Use ({$simUse['value']}) ≠ Plan ({$plan['shared_users']})\n";
        $conformite = false;
    } else if (intval($portLimit['value']) != $plan['shared_users']) {
        echo "   ❌ Port-Limit ({$portLimit['value']}) ≠ Plan ({$plan['shared_users']})\n";
        $conformite = false;
    } else {
        echo "   ✅ Configuration CORRECTE\n";
    }

    if ($conformite && $sessionCount <= $limit) {
        echo "   ✅ Fonctionnement nominal\n";
    }

    echo "\n";
}

echo "=== FIN DES TESTS ===\n";
