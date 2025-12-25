<?php
/**
 * Script de debug RADIUS - LOG toutes les requêtes
 */

// Activer le logging pour radius.php
$logFile = '/tmp/radius_debug_detailed.log';

function logDebug($message, $data = [])
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $log = "\n=== $timestamp ===\n";
    $log .= "$message\n";
    if (!empty($data)) {
        $log .= print_r($data, true) . "\n";
    }
    file_put_contents($logFile, $log, FILE_APPEND);
}

// Log tout ce qui arrive
logDebug("NEW REQUEST", [
    'Action' => $_SERVER['HTTP_X_FREERADIUS_SECTION'] ?? $_GET['action'] ?? 'unknown',
    'GET' => $_GET,
    'POST' => $_POST,
    'HEADERS' => getallheaders(),
    'SERVER' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR']
    ]
]);

// Continuer avec radius.php normal
include "radius.php";
