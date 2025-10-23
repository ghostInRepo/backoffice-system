<?php
// Simple IP-based rate limiter using file storage under var/rate_limit
// Not suitable for high-scale production but OK for local/dev/demo.

// Attempt to use Redis if available
function rl_get_redis() {
    static $client = '__uninitialized__';
    if ($client !== '__uninitialized__') return $client;
    $cfg = require __DIR__ . '/../config/config.php';
    $rCfg = $cfg['redis'] ?? [];
    // If Redis is not configured, don't attempt connections
    if (empty($rCfg['enabled'])) {
        $client = null;
        return $client;
    }

    // try phpredis first if available
    if (class_exists('Redis')) {
        try {
            $cls = 'Redis';
            $r = new $cls();
            // Set a shorter timeout to fail fast if Redis is down
            $r->connect($rCfg['host'] ?? '127.0.0.1', $rCfg['port'] ?? 6379, 0.5);
            if (!empty($rCfg['auth'])) $r->auth($rCfg['auth']);
            $client = $r;
            return $client;
        } catch (Exception $e) {
            // continue to try Predis
            error_log("phpredis connection failed: " . $e->getMessage());
        }
    }

    // try Predis if phpredis failed or not available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (class_exists('Predis\Client')) {
            try {
                $cls = 'Predis\\Client';
                $options = [
                    'parameters' => [
                        'scheme' => 'tcp',
                        'host' => $rCfg['host'] ?? '127.0.0.1',
                        'port' => $rCfg['port'] ?? 6379,
                        // fail fast if Redis is down
                        'timeout' => 0.5
                    ]
                ];
                $r = new $cls($options);
                if (!empty($rCfg['auth'])) $r->auth($rCfg['auth']);
                // Test connection before returning
                $r->ping();
                $client = $r;
                return $client;
            } catch (Exception $e) {
                error_log("Predis connection failed: " . $e->getMessage());
            }
        }
    }
    $client = null;
    return $client;
}

function rl_use_redis() {
    return rl_get_redis() !== null;
}

function rl_key($ip, $suffix) {
    $name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip);
    return "rate:{$name}:{$suffix}";
}

function rl_get($ip) {
    if (rl_use_redis()) {
        $r = rl_get_redis();
        $k = rl_key($ip, 'data');
        $val = $r->get($k);
        if (!$val) return null;
        return json_decode($val, true);
    }
    // file fallback
    $f = __DIR__ . '/../var/rate_limit/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip) . '.json';
    if (!file_exists($f)) return null;
    $raw = @file_get_contents($f);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function rl_save($ip, $data) {
    if (rl_use_redis()) {
        $r = rl_get_redis();
        $k = rl_key($ip, 'data');
        $r->set($k, json_encode($data));
        // set key TTL slightly longer than lock time if present
        if (!empty($data['lock_until'])) {
            $ttl = max(60, $data['lock_until'] - time());
            $r->expire($k, $ttl + 60);
        } else {
            $r->expire($k, 3600); // 1 hour
        }
        return;
    }
    $d = __DIR__ . '/../var/rate_limit'; if (!is_dir($d)) mkdir($d, 0755, true);
    $f = $d . '/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip) . '.json';
    file_put_contents($f, json_encode($data));
}

function rl_clear($ip) {
    if (rl_use_redis()) {
        $r = rl_get_redis();
        $r->del([rl_key($ip,'data')]);
        return;
    }
    $f = __DIR__ . '/../var/rate_limit/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip) . '.json';
    if (file_exists($f)) @unlink($f);
}

function rl_is_blocked($ip) {
    $d = rl_get($ip);
    if (!$d) return false;
    if (!empty($d['lock_until']) && time() < $d['lock_until']) return true;
    return false;
}

function rl_lock_remaining($ip) {
    $d = rl_get($ip);
    if (!$d || empty($d['lock_until'])) return 0;
    $rem = $d['lock_until'] - time();
    return $rem > 0 ? $rem : 0;
}

function rl_register_attempt($ip, $success = false) {
    $maxAttempts = 100; // allow more failures for testing
    $window = 60; // 1 minute window for counting attempts
    $blockDuration = 30; // 30 seconds lockout

    if ($success) {
        rl_clear($ip);
        return;
    }

    $d = rl_get($ip) ?: [];
    $now = time();
    if (empty($d['first_attempt']) || ($now - ($d['first_attempt'] ?? 0)) > $window) {
        // reset window
        $d['first_attempt'] = $now;
        $d['attempts'] = 1;
    } else {
        $d['attempts'] = ($d['attempts'] ?? 0) + 1;
    }

    if (($d['attempts'] ?? 0) >= $maxAttempts) {
        $d['lock_until'] = $now + $blockDuration;
    }

    rl_save($ip, $d);
}
