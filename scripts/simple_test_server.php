<?php
// Basic test server
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force testing environment
putenv('TEST_ALLOW_LOGIN=true');
putenv('APP_ENV=testing');
$_ENV['TEST_ALLOW_LOGIN'] = 'true';
$_ENV['APP_ENV'] = 'testing';
$_SERVER['TEST_ALLOW_LOGIN'] = 'true';
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Simple test endpoint responder
if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, '/__test_login') === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'OK',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'post_data' => $_POST,
            'env' => [
                'TEST_ALLOW_LOGIN' => getenv('TEST_ALLOW_LOGIN'),
                'APP_ENV' => getenv('APP_ENV'),
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR']
            ]
        ]);
        exit;
    }
}

// Otherwise pass to main application
return false;