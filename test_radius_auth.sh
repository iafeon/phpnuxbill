#!/bin/bash

# Script pour tester l'authentification RADIUS via curl
# Simule ce que Mikrotik envoie à FreeRADIUS via radius.php

echo "=== TEST AUTHENTIFICATION RADIUS ==="
echo ""

USERNAME="lebazol"
PASSWORD="26182820"

echo "Username: $USERNAME"
echo "Password: $PASSWORD"
echo ""

echo "--- Test 1: Authorize (activation voucher ou autorisation) ---"
curl -s -X POST "http://127.0.0.1/phpnuxbill/radius.php" \
  -H "X-FreeRadius-Section: authorize" \
  -d "User-Name=$USERNAME" \
  -d "User-Password=$PASSWORD" | jq . 2>/dev/null || curl -s -X POST "http://127.0.0.1/phpnuxbill/radius.php" \
  -H "X-FreeRadius-Section: authorize" \
  -d "User-Name=$USERNAME" \
  -d "User-Password=$PASSWORD"

echo ""
echo ""

echo "--- Test 2: Authenticate (vérification credentials) ---"
curl -s -X POST "http://127.0.0.1/phpnuxbill/radius.php" \
  -H "X-FreeRadius-Section: authenticate" \
  -d "User-Name=$USERNAME" \
  -d "User-Password=$PASSWORD" | jq . 2>/dev/null || curl -s -X POST "http://127.0.0.1/phpnuxbill/radius.php" \
  -H "X-FreeRadius-Section: authenticate" \
  -d "User-Name=$USERNAME" \
  -d "User-Password=$PASSWORD"

echo ""
echo ""

echo "--- Vérification radreply pour $USERNAME ---"
echo "SELECT username, attribute, op, value FROM radreply WHERE username='$USERNAME';" | mysql -u root radius 2>/dev/null || echo "Erreur accès DB"

echo ""
echo "=== FIN TEST ==="
