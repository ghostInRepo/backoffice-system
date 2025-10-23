<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set environment variables for testing
putenv('TEST_ALLOW_LOGIN=true');
putenv('APP_ENV=testing');
$_ENV['TEST_ALLOW_LOGIN'] = 'true';
$_ENV['APP_ENV'] = 'testing';

echo "=== TEST SCRIPT STARTING ===\n";
echo "TEST_ALLOW_LOGIN (getenv): " . getenv('TEST_ALLOW_LOGIN') . "\n";
echo "APP_ENV (getenv): " . getenv('APP_ENV') . "\n";

// Make a simple request to a test endpoint
$baseUrl = $argv[1] ?? 'http://localhost:8000';
$url = $baseUrl . '/__test_login';

echo "Initializing request to $url\n";

// Simple POST to test login endpoint
$ch = curl_init($url);
if ($ch === false) {
    die("Failed to initialize CURL\n");
}

echo "Setting CURL options...\n";

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

echo "Sending request...\n";
$res = curl_exec($ch);

if ($res === false) {
    echo "CURL error: " . curl_error($ch) . "\n";
    echo "CURL errno: " . curl_errno($ch) . "\n";
    die("Request failed\n");
}

$info = curl_getinfo($ch);
echo "=== REQUEST INFO ===\n";
foreach ($info as $key => $value) {
    if (is_string($value) || is_numeric($value)) {
        echo "$key: $value\n";
    }
}

echo "=== RESPONSE ===\n";
echo "HTTP code: " . ($info['http_code'] ?? 'n/a') . "\n";
echo "Response: " . substr($res, 0, 1000) . "\n";

curl_close($ch);

// Original test code...
$baseUrl = $argv[1] ?? 'http://localhost/backoffice_system/public';
$cookieDir = __DIR__;

function curl_post($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // we want to see 302/403 responses
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Role Test');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // Add headers to ensure REMOTE_ADDR is set
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Forwarded-For: 127.0.0.1',
        'Client-IP: 127.0.0.1'
    ]);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    error_log("curl_post to $url: " . $info['http_code'] . " - " . substr($res, 0, 500));
    curl_close($ch);
    return [$res, $info];
}
function curl_get($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // we want to see 302/403 responses
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Role Test');
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res, $info];
}

// create or update users
$agent = ['email' => 'role_agent@example.local', 'name' => 'Agent User', 'password' => 'AgentPass123!', 'role'=>'agent'];
$admin = ['email' => 'role_admin@example.local', 'name' => 'Admin User', 'password' => 'AdminPass123!', 'role'=>'admin'];

foreach ([$agent, $admin] as $u) {
    echo "Registering {$u['email']}...\n";
    list($b,$i) = curl_post($baseUrl . '/register', ['name'=>$u['name'],'email'=>$u['email'],'password'=>$u['password'],'role'=>$u['role']], $cookieDir . '/' . md5($u['email']) . '.cookies');
    echo "HTTP: " . ($i['http_code'] ?? 'n/a') . "\n";
}

// Login and test roles
function test_role($user, $cookieFile) {
    global $baseUrl;
    // Use test login endpoint
    echo "Testing {$user['email']} role...\n";
    list($tb,$ti) = curl_post($baseUrl . '/__test_login', ['email'=>$user['email']], $cookieFile);
    $tcode = $ti['http_code'] ?? 0;
    if ($tcode !== 200) {
        fwrite(STDERR, "Test-login failed for {$user['email']} (HTTP $tcode)\n");
        fwrite(STDERR, "Response: " . substr($tb, 0, 1000) . "\n");
        return ['code' => $tcode, 'error' => true];
    }
    echo "Test-login OK\n";
    
    // Check admin role access
    list($roleRes,$roleInfo) = curl_post($baseUrl . '/__role_check', ['email'=>$user['email'],'role'=>'admin'], $cookieFile);
    $roleCode = $roleInfo['http_code'] ?? 0;
    return ['code' => $roleCode, 'body' => $roleRes];
}

// Test agent (should be forbidden)
$agentCookie = $cookieDir . '/agent.cookies'; if (file_exists($agentCookie)) unlink($agentCookie);
$agentResult = test_role($agent, $agentCookie);
if ($agentResult['code'] !== 403) {
    fwrite(STDERR, "FAIL: Agent not correctly restricted from admin role (HTTP {$agentResult['code']})\n");
    exit(10);
}
echo "Agent correctly not an admin\n";

// Test admin (should be allowed)
$adminCookie = $cookieDir . '/admin.cookies'; if (file_exists($adminCookie)) unlink($adminCookie);
$adminResult = test_role($admin, $adminCookie);
if ($adminResult['code'] !== 200) {
    fwrite(STDERR, "FAIL: Admin role not correctly recognized (HTTP {$adminResult['code']})\n");
    exit(11);
}
echo "Admin correctly recognized as admin\n";

echo "Role tests passed.\n";
exit(0);