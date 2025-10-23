<?php
// Set environment variables for testing
putenv('TEST_ALLOW_LOGIN=true');
putenv('APP_ENV=testing');
$_ENV['TEST_ALLOW_LOGIN'] = 'true';
$_ENV['APP_ENV'] = 'testing';

$baseUrl = 'http://localhost/backoffice_system/public/test_endpoints.php';

echo "=== TEST SCRIPT STARTING ===\n";
echo "TEST_ALLOW_LOGIN (getenv): " . getenv('TEST_ALLOW_LOGIN') . "\n";
echo "APP_ENV (getenv): " . getenv('APP_ENV') . "\n";
echo "Base URL: $baseUrl\n";

// Make a simple request to the test login endpoint
$url = $baseUrl . '/__test_login';
echo "Testing endpoint: $url\n\n";

// Initialize cURL
$ch = curl_init($url);
if ($ch === false) {
    die("Failed to initialize CURL\n");
}

// Set options
$options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['email' => 'test@example.com']),
    CURLOPT_HTTPHEADER => [
        'X-Forwarded-For: 127.0.0.1',
        'Client-IP: 127.0.0.1'
    ],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_VERBOSE => true
];

foreach ($options as $option => $value) {
    if (curl_setopt($ch, $option, $value) === false) {
        echo "Failed to set option: $option\n";
    }
}

echo "=== SENDING REQUEST ===\n";
$res = curl_exec($ch);

if ($res === false) {
    echo "CURL error: " . curl_error($ch) . "\n";
    echo "CURL errno: " . curl_errno($ch) . "\n";
    die("Request failed\n");
}

$info = curl_getinfo($ch);
echo "\n=== RESPONSE INFO ===\n";
foreach ($info as $key => $value) {
    if (is_string($value) || is_numeric($value)) {
        echo "$key: $value\n";
    }
}

echo "\n=== RESPONSE BODY ===\n";
echo $res . "\n";

curl_close($ch);