<?php
// Set environment variables for testing
putenv('TEST_ALLOW_LOGIN=true');
putenv('APP_ENV=testing');
$_ENV['TEST_ALLOW_LOGIN'] = 'true';
$_ENV['APP_ENV'] = 'testing';
$_SERVER['TEST_ALLOW_LOGIN'] = 'true';
$_SERVER['APP_ENV'] = 'testing';

$baseUrl = 'http://localhost/backoffice_system/public';

echo "=== TEST SCRIPT STARTING ===\n";
echo "TEST_ALLOW_LOGIN (getenv): " . getenv('TEST_ALLOW_LOGIN') . "\n";
echo "APP_ENV (getenv): " . getenv('APP_ENV') . "\n";
echo "Base URL: $baseUrl\n";

// Make a simple request to the test login endpoint
$url = $baseUrl . '/__test_login';
echo "Testing endpoint: $url\n";

// Initialize cURL
$ch = curl_init($url);
if ($ch === false) {
    die("Failed to initialize CURL\n");
}

// Set options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['email' => 'test@example.com']),
    CURLOPT_HTTPHEADER => [
        'X-Forwarded-For: 127.0.0.1',
        'Client-IP: 127.0.0.1'
    ],
    CURLOPT_VERBOSE => true,
    CURLOPT_SSL_VERIFYPEER => false
]);

// Send request
$res = curl_exec($ch);
if ($res === false) {
    echo "CURL error: " . curl_error($ch) . "\n";
    echo "CURL errno: " . curl_errno($ch) . "\n";
    die("Request failed\n");
}

// Get request info
$info = curl_getinfo($ch);
echo "=== RESPONSE ===\n";
echo "HTTP code: " . ($info['http_code'] ?? 'n/a') . "\n";
echo "Response: " . substr($res, 0, 1000) . "\n";

curl_close($ch);