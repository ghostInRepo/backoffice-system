<?php
// Helper: Check if an IP matches a pattern (including CIDR)
function ip_matches_pattern($ip, $pattern) {
    if (!strpos($pattern, '/')) {
        return $pattern === $ip;
    }

    // Handle CIDR notation
    list($subnet, $bits) = explode('/', $pattern);
    if ($subnet === '127.0.0.1' && $bits == '32') {
        return $ip === '127.0.0.1' || $ip === '::1' || $ip === '::ffff:127.0.0.1';
    }

    return $ip === $subnet;
}

// Get config
$cfg = require __DIR__ . '/../config/config.php';
echo "=== CONFIG ===\n";
echo json_encode($cfg['tests'], JSON_PRETTY_PRINT) . "\n";

// Get environment
echo "\n=== ENVIRONMENT ===\n";
echo "TEST_ALLOW_LOGIN (getenv): " . getenv('TEST_ALLOW_LOGIN') . "\n";
echo "APP_ENV (getenv): " . getenv('APP_ENV') . "\n";
echo "Is CLI: " . (php_sapi_name() === 'cli' ? 'yes' : 'no') . "\n";

// Test some IP addresses
$testIps = [
    '127.0.0.1',
    '::1',
    '::ffff:127.0.0.1',
    '192.168.1.1'
];

echo "\n=== IP CHECKS ===\n";
foreach ($cfg['tests']['allowed_ci_ips'] as $pattern) {
    echo "Pattern: $pattern\n";
    foreach ($testIps as $ip) {
        echo "  $ip: " . (ip_matches_pattern($ip, $pattern) ? 'allowed' : 'denied') . "\n";
    }
}