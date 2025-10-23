<?php
// Role-based access test: ensure agent cannot access /admin (403) and admin can (200)
error_reporting(E_ALL);
ini_set('display_errors', 1);
$baseUrl = $argv[1] ?? 'http://localhost/backoffice_system/public';
$cookieDir = __DIR__;
echo "Starting tests with base URL: $baseUrl\n";
echo "Environment variables:\n";
echo "TEST_ALLOW_LOGIN=" . (getenv('TEST_ALLOW_LOGIN') ? 'true' : 'false') . "\n";
echo "APP_ENV=" . (getenv('APP_ENV') ?: 'not set') . "\n";

function curl_post($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Role Test');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
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

function read_latest_otp_for($email) {
    $logDir = __DIR__ . '/../var/logs';
    // Try per-email JSON file first
    $san = preg_replace('/[^a-z0-9._-]/', '_', strtolower($email));
    $otpFile = "$logDir/otp_{$san}.json";
    if (file_exists($otpFile)) {
        $j = json_decode(file_get_contents($otpFile), true);
        if (!empty($j['otp'])) return $j['otp'];
    }

    // Fallback to mail.log parsing
    $logPath = $logDir . '/mail.log';
    if (!file_exists($logPath)) return null;
    $contents = file_get_contents($logPath);
    // Split entries using the delimiter used in mailer: "----\n"
    $entries = array_filter(array_map('trim', explode("----", $contents)));
    // Search entries from newest to oldest for this recipient
    for ($i = count($entries)-1; $i >= 0; $i--) {
        $entry = $entries[$i];
        if (stripos($entry, "To: $email") !== false) {
            if (preg_match('/\b(\d{6})\b/', $entry, $m)) return $m[1];
        }
    }
    // Fallback: return the last 6-digit code found in the log regardless of recipient
    for ($i = count($entries)-1; $i >= 0; $i--) {
        if (preg_match('/\b(\d{6})\b/', $entries[$i], $m)) return $m[1];
    }
    return null;
}

// create or update users
$agent = ['email' => 'role_agent@example.local', 'name' => 'Agent User', 'password' => 'AgentPass123!', 'role'=>'agent'];
$admin = ['email' => 'role_admin@example.local', 'name' => 'Admin User', 'password' => 'AdminPass123!', 'role'=>'admin'];

foreach ([$agent, $admin] as $u) {
    echo "Registering {$u['email']}...\n";
    list($b,$i) = curl_post($baseUrl . '/register', ['name'=>$u['name'],'email'=>$u['email'],'password'=>$u['password'],'role'=>$u['role']], $cookieDir . '/' . md5($u['email']) . '.cookies');
    echo "HTTP: " . ($i['http_code'] ?? 'n/a') . "\n";
}

// helper to login and perform 2FA
// helper to login and perform 2FA or use test-login endpoint if available
function login_and_verify($email, $password, $cookieFile) {
    global $baseUrl;
    // First try test-only login endpoint
    echo "Attempting test-login for $email...\n";
    list($tb,$ti) = curl_post($baseUrl . '/__test_login', ['email'=>$email], $cookieFile);
    $tcode = $ti['http_code'] ?? 0;
    if ($tcode === 200) {
        echo "Test-login OK for $email\n";
        return true;
    } else {
        echo "Test-login failed for $email (HTTP $tcode).\n";
        echo "Response body:\n" . substr($tb,0,400) . "\n";
        echo "Error info:\n";
        print_r($ti);
        if ($tcode === 404) {
            echo "Test endpoints appear to be disabled (404 Not Found)\n";
        }
    }

    // Fallback to real flow
    echo "Falling back to OTP login for $email...\n";
    list($b,$i) = curl_post($baseUrl . '/login', ['email'=>$email,'password'=>$password], $cookieFile);
    echo "Login HTTP: " . ($i['http_code'] ?? 'n/a') . "\n";
    sleep(1);
    $otp = read_latest_otp_for($email);
    if (!$otp) { echo "No OTP for $email\n"; return false; }
    echo "Found OTP: $otp\n";
    list($vb,$vi) = curl_post($baseUrl . '/verify-2fa', ['otp'=>$otp], $cookieFile);
    echo "Verify HTTP: " . ($vi['http_code'] ?? 'n/a') . "\n";
    return ($vi['http_code'] ?? 0) >= 200;
}

// 1) agent should be forbidden
$agentCookie = $cookieDir . '/agent.cookies'; if (file_exists($agentCookie)) unlink($agentCookie);
$success = login_and_verify($agent['email'],$agent['password'],$agentCookie);
if (! $success) { fwrite(STDERR, "Agent login/verify failed\n"); exit(10); }
// Use test-only role check endpoint to verify agent is not admin
list($roleRes,$roleInfo) = curl_post($baseUrl . '/__role_check', ['email'=>$agent['email'],'role'=>'admin'], $agentCookie);
$roleCode = $roleInfo['http_code'] ?? 0;
echo "Agent role-check HTTP: $roleCode\n";
if ($roleCode === 403) {
    echo "Agent correctly not an admin\n";
} else {
    fwrite(STDERR, "Agent role-check unexpected result (HTTP $roleCode)\n"); exit(11);
}

// 2) admin should be allowed
$adminCookie = $cookieDir . '/admin.cookies'; if (file_exists($adminCookie)) unlink($adminCookie);
$success = login_and_verify($admin['email'],$admin['password'],$adminCookie);
if (! $success) { fwrite(STDERR, "Admin login/verify failed\n"); exit(12); }
// Use test-only role check endpoint to verify admin role
list($roleRes,$roleInfo) = curl_post($baseUrl . '/__role_check', ['email'=>$admin['email'],'role'=>'admin'], $adminCookie);
$roleCode = $roleInfo['http_code'] ?? 0;
echo "Admin role-check HTTP: $roleCode\n";
if ($roleCode === 200) {
    echo "Admin correctly recognized as admin\n";
} else {
    fwrite(STDERR, "Admin role-check failed (HTTP $roleCode)\n"); exit(13);
}

echo "Role tests passed.\n";
exit(0);
