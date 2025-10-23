<?php
// Front controller
session_start();

require_once __DIR__ . '/../config/config.php';
$db = require __DIR__ . '/../config/db.php';

// Allow test endpoints only under specific conditions
function allow_test_endpoints()
{
    error_log("=== TEST ENDPOINT ACCESS CHECK ===");
    error_log("REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set'));
    error_log("HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set'));
    error_log("HTTP_CLIENT_IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'not set'));
    error_log("TEST_ALLOW_LOGIN (getenv): " . getenv('TEST_ALLOW_LOGIN'));
    error_log("APP_ENV (getenv): " . getenv('APP_ENV'));

    // For CLI scripts and web requests, check if it's from localhost
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $clientIp = $_SERVER['HTTP_CLIENT_IP'] ?? '';
    $isCliLocal = php_sapi_name() === 'cli';

    error_log("Remote Address: $remoteAddr");
    error_log("X-Forwarded-For: $forwardedFor");
    error_log("Client IP: $clientIp");
    error_log("Is CLI: " . ($isCliLocal ? 'yes' : 'no'));

    // First check: config flag
    $cfg = require __DIR__ . '/../config/config.php';
    error_log("Config: " . json_encode($cfg['tests'] ?? []));

    if (empty($cfg['tests']['allow_test_login'])) {
        error_log("Disabled: test_allow_login not enabled in config");
        return false;
    }

    // Check for localhost addresses
    $isLocalhost = false;
    if ($remoteAddr === '127.0.0.1' || $remoteAddr === '::1' || $remoteAddr === '::ffff:127.0.0.1' || strpos($remoteAddr, '127.') === 0) {
        $isLocalhost = true;
        error_log("[allow_test_endpoints] Localhost detected via REMOTE_ADDR");
    }

    if ($forwardedFor) {
        $ips = array_map('trim', explode(',', $forwardedFor));
        $ip = $ips[0] ?? '';
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '::ffff:127.0.0.1') {
            $isLocalhost = true;
            error_log("[allow_test_endpoints] Localhost detected via X-Forwarded-For");
        }
    }

    if ($isLocalhost || $isCliLocal) {
        error_log("[allow_test_endpoints] Access allowed (localhost or CLI)");
        return true;
    }

    // Otherwise require explicit env flag and allowed CI ranges
    $envFlag = (getenv('TEST_ALLOW_LOGIN') === 'true' || getenv('APP_ENV') === 'testing');
    if (!$envFlag) {
        error_log('[allow_test_endpoints] Env flag not set');
        return false;
    }

    $ciRanges = $cfg['tests']['allowed_ci_ips'] ?? [];
    foreach ($ciRanges as $range) {
        if (empty($range)) continue;
        // Exact match
        if ($range === $remoteAddr || $range === $clientIp || $range === $forwardedFor) {
            error_log("[allow_test_endpoints] IP check passed: Matched $range");
            return true;
        }
        // CIDR support for IPv4
        if (strpos($range, '/') !== false && $remoteAddr) {
            list($subnet, $bits) = explode('/', $range);
            $ip_decimal = ip2long($remoteAddr);
            $subnet_decimal = ip2long($subnet);
            if ($ip_decimal !== false && $subnet_decimal !== false) {
                $mask = -1 << (32 - (int)$bits);
                if (($ip_decimal & $mask) === ($subnet_decimal & $mask)) {
                    error_log("[allow_test_endpoints] CIDR match: $range");
                    return true;
                }
            }
        }
    }

    error_log('[allow_test_endpoints] No matching IP range');
    return false;
}

// Routing
$rawRequestUri = $_SERVER['REQUEST_URI'] ?? null;
$uri = $rawRequestUri ? parse_url($rawRequestUri, PHP_URL_PATH) : '/';
$base = '/backoffice_system/public';
$path = '/' . trim(substr($uri, strlen($base)), '/');
error_log("Raw REQUEST_URI: " . ($rawRequestUri ?? 'not set'));
error_log("Parsed URI: $uri");
error_log("Final path: $path");

switch ($path) {
    case '/__test_login':
        // Test-only login endpoint: sets session['user'] directly when enabled in config
        error_log("[/__test_login] Testing endpoint access...");
        error_log("[/__test_login] TEST_ALLOW_LOGIN=" . getenv('TEST_ALLOW_LOGIN'));
        error_log("[/__test_login] APP_ENV=" . getenv('APP_ENV'));

        $cfg = require __DIR__ . '/../config/config.php';
        error_log("[/__test_login] Test config: " . json_encode($cfg['tests'] ?? []));

        $allowed = allow_test_endpoints();
        error_log("[/__test_login] allow_test_endpoints() returned: " . ($allowed ? 'true' : 'false'));

        if (!$allowed) {
            error_log("[/__test_login] Access denied by allow_test_endpoints()");
            http_response_code(404);
            echo 'Test endpoints disabled';
            break;
        }

        error_log("[/__test_login] Access allowed");
        // Accept POST {email}
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            if ($email) {
                // load user from DB and set session
                $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $u = $stmt->fetch();
                if ($u) {
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email']];
                    $_SESSION['role'] = $u['role'];
                    $_SESSION['last_activity'] = time();
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'OK', 'session_id' => session_id(), 'role' => $u['role']]);
                    break;
                }
            }
        }
        http_response_code(400);
        echo 'Bad Request';
        break;

    case '/__role_check':
        // Test-only endpoint to check a user's role directly (POST {email,role})
        if (!allow_test_endpoints()) {
            http_response_code(404);
            echo 'Not Found';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            if ($email && $role) {
                $stmt = $db->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $u = $stmt->fetch();
                if ($u && $u['role'] === $role) {
                    http_response_code(200);
                    echo 'OK';
                    break;
                } else {
                    http_response_code(403);
                    echo 'Forbidden';
                    break;
                }
            }
        }
        http_response_code(400);
        echo 'Bad Request';
        break;

    case '/login':
        $c = new AuthController($db);
        $c->login();
        break;

    case '/register':
        $c = new AuthController($db);
        $c->register();
        break;

    case '/verify-2fa':
        $c = new AuthController($db);
        $c->verify2fa();
        break;

    case '/resend-otp':
        $c = new AuthController($db);
        $c->resendOtp();
        break;

    case '/logout':
        $c = new AuthController($db);
        $c->logout();
        break;

    case '/dashboard':
        $c = new DashboardController($db);
        $c->index();
        break;

    case '/staff':
        $c = new StaffController($db);
        $c->index();
        break;

    case '/admin':
        $c = new AdminController($db);
        $c->index();
        break;

    case '/health':
        $c = new HealthController($db);
        $c->check();
        break;

    case '/health/redis':
        $c = new HealthController($db);
        $c->checkRedis();
        break;

    default:
        http_response_code(404);
        echo "Not Found";
}
