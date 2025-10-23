<?php
// Quick script to clear rate limiter data from Redis
$cfg = require __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../helpers/rate_limiter.php";

$redis = rl_get_redis();
if (!$redis) {
    echo "Redis not available\n";
    exit(1);
}

// Delete all rate limit keys
$keys = $redis->keys('rate:*');
// Convert Predis response to array
if (is_object($keys)) {
    // Handle Predis response - it implements Iterator
    $keyArray = [];
    foreach ($keys as $key) {
        $keyArray[] = $key;
    }
    $keys = $keyArray;
}

if (!empty($keys)) {
    $redis->del($keys);
    echo "Cleared " . count($keys) . " rate limit keys from Redis\n";
} else {
    echo "No rate limit keys found in Redis\n";
}
