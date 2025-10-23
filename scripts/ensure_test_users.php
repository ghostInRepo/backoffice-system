<?php
// Ensure test users exist (creates or updates). Run before role_test.php in CI/dev.
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

$users = [
    ['email'=>'role_agent@example.local','name'=>'Agent User','password'=>'AgentPass123!','role'=>'agent'],
    ['email'=>'role_admin@example.local','name'=>'Admin User','password'=>'AdminPass123!','role'=>'admin']
];

foreach ($users as $u) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$u['email']]);
    $existing = $stmt->fetch();
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    if ($existing) {
        $stmt = $db->prepare('UPDATE users SET name = ?, password = ?, role = ? WHERE id = ?');
        $stmt->execute([$u['name'],$hash,$u['role'],$existing['id']]);
        echo "Updated user {$u['email']}\n";
    } else {
        $stmt = $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $stmt->execute([$u['name'],$u['email'],$hash,$u['role']]);
        echo "Created user {$u['email']}\n";
    }
}

echo "Done.\n";
exit(0);
