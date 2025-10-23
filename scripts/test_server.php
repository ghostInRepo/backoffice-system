<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Testing server wrapper with hardcoded environment
echo "=== STARTING TEST SERVER ===\n";

// Force environment variables for testing
putenv('TEST_ALLOW_LOGIN=true');
putenv('APP_ENV=testing');

// Set in all places
$_ENV['TEST_ALLOW_LOGIN'] = 'true';
$_ENV['APP_ENV'] = 'testing';
$_SERVER['TEST_ALLOW_LOGIN'] = 'true';
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Print current environment state
$env_vars = [
    'TEST_ALLOW_LOGIN' => ['getenv' => getenv('TEST_ALLOW_LOGIN'), 
                          'ENV' => $_ENV['TEST_ALLOW_LOGIN'] ?? 'not set', 
                          'SERVER' => $_SERVER['TEST_ALLOW_LOGIN'] ?? 'not set'],
    'APP_ENV' => ['getenv' => getenv('APP_ENV'), 
                  'ENV' => $_ENV['APP_ENV'] ?? 'not set', 
                  'SERVER' => $_SERVER['APP_ENV'] ?? 'not set']
];

echo "=== ENVIRONMENT STATE ===\n";
foreach ($env_vars as $var => $values) {
    foreach ($values as $source => $value) {
        echo "$var ($source): $value\n";
    }
}

// Check if request is for a test endpoint
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, '/__test_') === 0) {
    echo "Test endpoint requested: $uri\n";

    if ($uri === '/__test_login') {
        echo "Processing test login request\n";
        
        // Debug request details
        echo "=== REQUEST DETAILS ===\n";
        echo "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
        echo "REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . "\n";
        echo "X-Forwarded-For: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set') . "\n";
        echo "Client-IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'not set') . "\n";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Log POST data
            echo "POST data:\n";
            var_export($_POST);
            echo "\n";

            // Send success response
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'OK',
                'message' => 'Test endpoint responded successfully',
                'environment' => [
                    'TEST_ALLOW_LOGIN' => getenv('TEST_ALLOW_LOGIN'),
                    'APP_ENV' => getenv('APP_ENV'),
                    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
                    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
                    'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
                    'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? null
                ]
            ]);
            return true;
        } else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed', 'expected' => 'POST']);
            return true;
        }
    }
}

// If we get here, pass control to the main application
return false;

// Pre-load config to ensure it picks up the environment variables
$cfg = require __DIR__ . '/../config/config.php';
echo "Environment set:\n";
echo "TEST_ALLOW_LOGIN=" . getenv('TEST_ALLOW_LOGIN') . "\n";
echo "APP_ENV=" . getenv('APP_ENV') . "\n";
echo "Config tests.allow_test_login: " . ($cfg['tests']['allow_test_login'] ? 'true' : 'false') . "\n";

// Set server variables for localhost detection
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Serve request
require __DIR__ . '/../public/index.php';