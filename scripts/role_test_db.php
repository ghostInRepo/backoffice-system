<?php
// DB-only role test: checks that user roles are stored correctly and enforces expected outcomes.
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

$agentEmail = 'role_agent@example.local';
$adminEmail = 'role_admin@example.local';

function getRole($db, $email) {
    $stmt = $db->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    return $u['role'] ?? null;
}

$agentRole = getRole($db, $agentEmail);
$adminRole = getRole($db, $adminEmail);

echo "Agent role for $agentEmail: " . ($agentRole ?? 'NOT FOUND') . "\n";
echo "Admin role for $adminEmail: " . ($adminRole ?? 'NOT FOUND') . "\n";

$fail = false;
if ($agentRole !== null && $agentRole === 'admin') {
    fwrite(STDERR, "FAIL: agent user has admin role\n");
    $fail = true;
}
if ($adminRole !== 'admin') {
    fwrite(STDERR, "FAIL: admin user not recognized as admin\n");
    $fail = true;
}

if ($fail) {
    echo "Role DB test: FAILED\n";
    exit(2);
} else {
    echo "Role DB test: PASSED\n";
    exit(0);
}
