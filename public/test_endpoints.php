<?php
// Simple script to handle just the test endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Allow test endpoints only under specific conditions
function allow_test_endpoints() {
    error_log("=== TEST ENDPOINT ACCESS CHECK ===");
    error_log("REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set'));
    error_log("HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set'));
    error_log("HTTP_CLIENT_IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'not set'));

    // Check if running from CLI
    $isCliLocal = php_sapi_name() === 'cli';
    error_log("Is CLI: " . ($isCliLocal ? 'yes' : 'no'));

    // Get IP addresses from various sources
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $clientIp = $_SERVER['HTTP_CLIENT_IP'] ?? '';

    // Load config
    $cfg = require __DIR__ . '/../config/config.php';
    error_log("Config: " . json_encode($cfg['tests']));

    if (empty($cfg['tests']['allow_test_login'])) {
        error_log("Disabled: test_allow_login not enabled in config");
        return false;
    }

    // Check if IP matches allowed patterns
    $isAllowedIp = false;
    foreach ($cfg['tests']['allowed_ci_ips'] as $allowedIp) {
        if (ip_matches_pattern($remoteAddr, $allowedIp) || 
            ($forwardedFor && ip_matches_pattern($forwardedFor, $allowedIp)) ||
            ($clientIp && ip_matches_pattern($clientIp, $allowedIp))) {
            $isAllowedIp = true;
            error_log("IP check passed: Matched $allowedIp");
            break;
        }
    }

    if (!$isAllowedIp && !$isCliLocal) {
        error_log("Disabled: Not allowed IP and not CLI");
        return false;
    }

    error_log("Access allowed");
    return true;
}

// Very simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
error_log("Raw REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("Parsed URI: " . $uri);

$base = '/backoffice_system/public/test_endpoints.php'; // Base path including the script
error_log("Base path: " . $base);

$path = '/' . trim(substr($uri, strlen($base)), '/');
error_log("Final path: " . $path);

switch ($path) {
    case '/__test_login':
        error_log("Handling test login request");
        if (!allow_test_endpoints()) {
            http_response_code(404);
            echo 'Test endpoints disabled';
            break;
        }
        
        error_log("Test endpoints allowed");
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            if ($email) {
                error_log("Processing test login for: $email");
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'OK',
                    'message' => 'Test login successful',
                    'email' => $email
                ]);
                exit;
            }
        }
        http_response_code(400);
        echo 'Bad request';
        break;
    
    default:
        http_response_code(404);
        echo "Not Found";
        break;
}