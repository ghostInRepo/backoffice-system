<?php

class HealthController extends BaseController {
    public function check() {
        // Optional token protection with brute-force protection and logging
        $cfg = require __DIR__ . '/../config/config.php';
        require_once __DIR__ . '/../helpers/rate_limiter.php';
        require_once __DIR__ . '/../helpers/health_token.php';

        // Determine required token: prefer rotating token if enabled
        $healthCfg = $cfg['health'] ?? [];
        $requiredToken = '';
        $ht = null;
        $isRotating = !empty($healthCfg['force_rotate']) || !empty($healthCfg['rotate']);

        if ($isRotating) {
            // Get token and expiry from store
            $ht = ht_get_token();
            $requiredToken = $ht['token'] ?? '';
        } else {
            // Non-rotating: static token without expiry
            $ht = null;
            $requiredToken = $healthCfg['token'] ?? '';
        }

        // determine client IP for rate-limiting/logging
        $ip = $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['SERVER_ADDR'] ?? 'cli');

        // If blocked by rate limiter, return 429
        if (rl_is_blocked($ip)) {
            $rem = rl_lock_remaining($ip);
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'too_many_requests', 'retry_after' => $rem]);
            return;
        }

        if (!empty($requiredToken)) {
            $provided = '';
            // Check query param
            if (!empty($_GET['token'])) $provided = $_GET['token'];
            // Check Authorization header Bearer
            if (empty($provided) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                    $provided = $m[1];
                }
            }

            $isValid = false;
            
            if ($isRotating) {
                // Check current token with expiry for rotating tokens
                if ($provided === $requiredToken && time() < ($ht['expires_at'] ?? 0)) {
                    $isValid = true;
                }
                // Check previous token during overlap
                elseif (isset($ht['previous_token']) && isset($ht['previous_expires_at'])) {
                    if ($provided === $ht['previous_token'] && time() < $ht['previous_expires_at']) {
                        $isValid = true;
                    }
                }
            } else {
                // Simple token match for non-rotating tokens
                $isValid = $provided === $requiredToken;
            }

            if (!$isValid) {
                // Log failed attempt
                $logDir = __DIR__ . '/../var/logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $logFile = $logDir . '/health_auth.log';
                $when = date('c');
                $mask = $provided ? substr($provided, 0, 4) . '...' : '(none)';
                $entry = "[{$when}] FAILED health auth from {$ip} provided={$mask}\n";
                @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

                // register a failed attempt with rate limiter
                rl_register_attempt($ip, false);

                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'unauthorized']);
                return;
            }

            // on successful auth clear any prior attempts
            rl_register_attempt($ip, true);
        }
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'services' => []
        ];

        // Check Redis
        try {
            $redis = rl_get_redis();
            if (!$redis) {
                $health['services']['redis'] = [
                    'status' => 'info',
                    'message' => 'Redis not configured, using file-based rate limiting',
                    'storage' => 'file'
                ];
            } else {
                try {
                    // try ping first (phpredis)
                    if (method_exists($redis, 'ping')) {
                        $redis->ping();
                    } else {
                        // fallback for Predis
                        $redis->set('health_check', '1');
                        $redis->get('health_check');
                        $redis->del(['health_check']);
                    }
                    $health['services']['redis'] = [
                        'status' => 'ok',
                        'storage' => 'redis',
                        'driver' => class_exists('Redis') && get_class($redis) === 'Redis' ? 'phpredis' : 'predis'
                    ];
                } catch (Exception $connEx) {
                    // Connection failed but we have file fallback
                    $health['services']['redis'] = [
                        'status' => 'info',
                        'message' => 'Redis server not running, using file-based rate limiting',
                        'storage' => 'file',
                        'detail' => $connEx->getMessage()
                    ];
                }
            }
        } catch (Exception $e) {
            $health['services']['redis'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check MySQL
        try {
            $this->db->query('SELECT 1');
            $health['services']['mysql'] = ['status' => 'ok'];
        } catch (Exception $e) {
            $health['services']['mysql'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $health['status'] = 'error'; // MySQL is critical
        }

        // Check file system (var/logs writable)
        $logDir = __DIR__ . '/../var/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        if (is_writable($logDir)) {
            $health['services']['filesystem'] = ['status' => 'ok'];
        } else {
            $health['services']['filesystem'] = [
                'status' => 'error',
                'message' => 'var/logs not writable'
            ];
            $health['status'] = 'error';
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($health, JSON_PRETTY_PRINT);
    }

    public function checkRedis() {
        // Optional token protection with brute-force protection and logging
        $cfg = require __DIR__ . '/../config/config.php';
        require_once __DIR__ . '/../helpers/rate_limiter.php';
        require_once __DIR__ . '/../helpers/health_token.php';

        $healthCfg = $cfg['health'] ?? [];
        $requiredToken = '';
        $ht = null;
        $isRotating = !empty($healthCfg['force_rotate']) || !empty($healthCfg['rotate']);

        if ($isRotating) {
            // Get token and expiry from store
            $ht = ht_get_token();
            $requiredToken = $ht['token'] ?? '';
        } else {
            // Non-rotating: static token without expiry
            $ht = null;
            $requiredToken = $healthCfg['token'] ?? '';
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['SERVER_ADDR'] ?? 'cli');
        if (rl_is_blocked($ip)) {
            $rem = rl_lock_remaining($ip);
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'too_many_requests', 'retry_after' => $rem]);
            return;
        }

        if (!empty($requiredToken)) {
            $provided = '';
            if (!empty($_GET['token'])) $provided = $_GET['token'];
            if (empty($provided) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                    $provided = $m[1];
                }
            }

            $isValid = false;
            
            if ($isRotating) {
                // Check current token with expiry for rotating tokens
                if ($provided === $requiredToken && time() < ($ht['expires_at'] ?? 0)) {
                    $isValid = true;
                }
                // Check previous token during overlap
                elseif (isset($ht['previous_token']) && isset($ht['previous_expires_at'])) {
                    if ($provided === $ht['previous_token'] && time() < $ht['previous_expires_at']) {
                        $isValid = true;
                    }
                }
            } else {
                // Simple token match for non-rotating tokens
                $isValid = $provided === $requiredToken;
            }

            if (!$isValid) {
                $logDir = __DIR__ . '/../var/logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $logFile = $logDir . '/health_auth.log';
                $when = date('c');
                $mask = $provided ? substr($provided, 0, 4) . '...' : '(none)';
                $entry = "[{$when}] FAILED health auth (redis) from {$ip} provided={$mask}\n";
                @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

                rl_register_attempt($ip, false);

                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'unauthorized']);
                return;
            }

            rl_register_attempt($ip, true);
        }
        header('Content-Type: application/json');

        try {
            $redis = rl_get_redis();
            if (!$redis) {
                echo json_encode([
                    'status' => 'warning',
                    'message' => 'Redis not available. Using file-based rate limiting.',
                    'driver' => 'file'
                ]);
                return;
            }

            // Test connection and basic operations
            $testKey = 'health:test:' . uniqid();
            $redis->set($testKey, 'test');
            $val = $redis->get($testKey);
            $redis->del([$testKey]);

            $info = [
                'status' => 'ok',
                'driver' => class_exists('Redis') && get_class($redis) === 'Redis' ? 'phpredis' : 'predis',
                'connected' => true
            ];

            // Get Redis info if available
            if (method_exists($redis, 'info')) {
                $redisInfo = $redis->info();
                $info['version'] = $redisInfo['redis_version'] ?? 'unknown';
                $info['memory'] = $redisInfo['used_memory_human'] ?? 'unknown';
                $info['clients'] = $redisInfo['connected_clients'] ?? 'unknown';
            }

            echo json_encode($info, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}