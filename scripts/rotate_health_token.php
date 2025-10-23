<?php
/**
 * Rotate health token CLI
 * Usage: php scripts/rotate_health_token.php [--ttl=SECONDS] [--quiet]
 */

require_once __DIR__ . '/../helpers/health_token.php';

$options = [];
$ttl = null;

foreach ($argv as $i => $a) {
    if ($i === 1 && !preg_match('/^--/', $a)) {
        // First arg is ttl if not starting with --
        $ttl = (int)$a;
        continue;
    }
    if (strpos($a, '--ttl=') === 0) {
        $ttl = (int)substr($a, 6);
    }
    if ($a === '--quiet') $options['quiet'] = true;
}
$res = ht_force_rotate_now($ttl);

if (empty($options['quiet'])) {
    echo "Rotated health token:\n";
    echo "token: " . $res['token'] . "\n";
    echo "expires_at: " . date('c', $res['expires_at']) . "\n";
}

return 0;
