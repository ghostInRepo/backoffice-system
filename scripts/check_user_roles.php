<?php
// Debug helper: print stored role for given emails
require __DIR__ . '/../config/db.php';
$db = (require __DIR__ . '/../config/db.php');
$emails = [
    'role_agent@example.local',
    'role_admin@example.local'
];
foreach ($emails as $e) {
    $stmt = $db->prepare('SELECT id,email,role,created_at FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$e]);
    $u = $stmt->fetch();
    if ($u) {
        echo "Found user: {$u['email']} id={$u['id']} role={$u['role']} created={$u['created_at']}\n";
    } else {
        echo "User not found: $e\n";
    }
}

echo "-- All users count: ";
$c = $db->query('SELECT COUNT(*) as c FROM users')->fetch();
echo ($c['c'] ?? 0) . "\n";

echo "-- Dumping users table:\n";
$all = $db->query('SELECT id,email,role,created_at FROM users ORDER BY id ASC')->fetchAll();
foreach ($all as $u) {
    echo json_encode($u) . "\n";
}

exit(0);
