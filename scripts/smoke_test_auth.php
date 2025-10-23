<?php
// PHP cURL smoke test: register -> login -> read OTP from var/logs/mail.log -> verify 2FA -> fetch /dashboard
$baseUrl = $argv[1] ?? 'http://localhost/backoffice_system/public';
$testEmail = $argv[2] ?? 'phpsmoketest@example.local';
$testName = $argv[3] ?? 'PHP Smoke';
$testPass = $argv[4] ?? 'TestPass123!';

$cookieFile = __DIR__ . '/.cookies.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

echo "Base URL: $baseUrl\n";

function curl_get($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Smoke Test');
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res, $info];
}

function curl_post($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Smoke Test');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res, $info];
}

// 1) Register (ignore errors)
try {
    echo "Registering $testEmail...\n";
    list($regBody, $regInfo) = curl_post($baseUrl . '/register', [
        'name' => $testName,
        'email' => $testEmail,
        'password' => $testPass,
        'role' => 'agent'
    ], $cookieFile);
    echo "Register HTTP: " . ($regInfo['http_code'] ?? 'n/a') . "\n";
} catch (Exception $e) {
    echo "Register failed: " . $e->getMessage() . "\n";
}

// 2) Login
echo "Logging in...\n";
list($loginBody, $loginInfo) = curl_post($baseUrl . '/login', [
    'email' => $testEmail,
    'password' => $testPass
], $cookieFile);
$loginCode = $loginInfo['http_code'] ?? 0;
echo "Login HTTP: $loginCode\n";
if ($loginCode < 200 || $loginCode >= 400) {
    fwrite(STDERR, "Login failed with HTTP code $loginCode\n");
    exit(2);
}

// 3) Read OTP from var/logs/mail.log (tail)
$logPath = __DIR__ . '/../var/logs/mail.log';
if (!file_exists($logPath)) {
    echo "Mail log not found at $logPath\n"; exit(1);
}
$lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$otp = null;
for ($i = count($lines)-1; $i >= 0; $i--) {
    if (preg_match('/\b(\d{6})\b/', $lines[$i], $m)) { $otp = $m[1]; break; }
}
if (!$otp) { echo "OTP not found in mail.log\n"; exit(1); }
echo "Found OTP: $otp\n";

// 4) POST OTP to verify-2fa
list($verifyBody, $verifyInfo) = curl_post($baseUrl . '/verify-2fa', [ 'otp' => $otp ], $cookieFile);
$verifyCode = $verifyInfo['http_code'] ?? 0;
echo "Verify HTTP: $verifyCode\n";
if (!in_array($verifyCode, [200,301,302], true)) {
    fwrite(STDERR, "OTP verification failed with HTTP code $verifyCode\n");
    exit(3);
}

// 5) Fetch dashboard
list($dashBody, $dashInfo) = curl_get($baseUrl . '/dashboard', $cookieFile);
$dashCode = $dashInfo['http_code'] ?? 0;
echo "Dashboard HTTP: $dashCode\n";
if ($dashCode !== 200) {
    fwrite(STDERR, "Failed to fetch dashboard (HTTP $dashCode)\n");
    exit(4);
}
$snippet = substr($dashBody ?? '', 0, 800);
echo "--- Dashboard Snippet ---\n";
echo $snippet . "\n";

// Basic content assertions: ensure dashboard contains expected markers
$ok = false;
$checkStrings = ['Travel Backoffice', 'Dashboard', '<main', 'sidebar'];
foreach ($checkStrings as $s) {
    if (stripos($dashBody, $s) !== false) { $ok = true; break; }
}
if (! $ok) {
    fwrite(STDERR, "Dashboard content did not contain expected markers.\n");
    exit(5);
}

echo "Smoke test done.\n";
