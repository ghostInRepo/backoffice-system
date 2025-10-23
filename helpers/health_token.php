<?php
/**
 * health_token helper
 * Stores a rotating token in var/health_token.json and returns current valid token.
 */

function ht_store_path() {
    return __DIR__ . '/../var/health_token.json';
}

function ht_log_path() {
    return __DIR__ . '/../var/logs/health_rotation.log';
}

function ht_generate($bytes = 16) {
    try {
        return bin2hex(random_bytes($bytes));
    } catch (Exception $e) {
        // fallback
        return bin2hex(openssl_random_pseudo_bytes($bytes));
    }
}

function ht_read_store() {
    $p = ht_store_path();
    if (!file_exists($p)) return null;
    $raw = @file_get_contents($p);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function ht_write_store(array $data) {
    $p = ht_store_path();
    $d = dirname($p);
    if (!is_dir($d)) @mkdir($d, 0755, true);
    @file_put_contents($p, json_encode($data), LOCK_EX);
}

function ht_log($msg) {
    $p = ht_log_path();
    $d = dirname($p);
    if (!is_dir($d)) @mkdir($d, 0755, true);
    $line = '[' . date('c') . '] ' . $msg . "\n";
    @file_put_contents($p, $line, FILE_APPEND | LOCK_EX);
}

function ht_rotate($ttlSeconds = 86400) {
    $token = ht_generate(16);
    $expires = time() + (int)$ttlSeconds;

    // Get existing token to preserve as previous
    $existing = ht_read_store();

    $data = [
        'token' => $token,
        'expires_at' => $expires
    ];

    // Keep previous token active during overlap window
    if (!empty($existing['token']) && !empty($existing['expires_at'])) {
        $data['previous_token'] = $existing['token'];
        $data['previous_expires_at'] = $existing['expires_at'];
    }

    ht_write_store($data);
    ht_log("ROTATED token expires_in={$ttlSeconds} token=" . substr($token,0,8) . '...');
    return $data;
}

function ht_get_token() {
    // If config has a static token, honor it (no rotation)
    $cfg = @include __DIR__ . '/../config/config.php';
    $healthCfg = $cfg['health'] ?? [];
    $static = $healthCfg['token'] ?? '';
    $rotateEnabled = $healthCfg['rotate'] ?? true;
    $rotateInterval = $healthCfg['rotate_interval'] ?? 86400;

    if (!empty($static) && empty($healthCfg['force_rotate'])) {
        return ['token' => $static, 'expires_at' => null, 'source' => 'config'];
    }

    $store = ht_read_store();
    if (!$store || empty($store['token']) || empty($store['expires_at']) || time() >= ($store['expires_at'] ?? 0)) {
        $previous = $store ?? [];
        if ($rotateEnabled) {
            $store = ht_rotate($rotateInterval);
            // Add previous token to store with original expiry
            if (!empty($previous['token']) && !empty($previous['expires_at'])) {
                $store['previous_token'] = $previous['token'];
                $store['previous_expires_at'] = $previous['expires_at'];
            }
            ht_write_store($store);
        } else {
            // not rotating and no stored token: create one with long expiry
            $store = ht_rotate($rotateInterval);
        }
    }

    return [
        'token' => $store['token'],
        'expires_at' => $store['expires_at'],
        'source' => 'store',
        'previous_token' => $store['previous_token'] ?? null,
        'previous_expires_at' => $store['previous_expires_at'] ?? null
    ];
}

function ht_force_rotate_now($ttlSeconds = null) {
    $cfg = @include __DIR__ . '/../config/config.php';
    $healthCfg = $cfg['health'] ?? [];
    $interval = $ttlSeconds ?? ($healthCfg['rotate_interval'] ?? 86400);
    return ht_rotate($interval);
}
