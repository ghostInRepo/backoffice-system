<?php
// Usage: php create_admin.php admin@example.com password "Admin Name"
if ($argc < 4) {
    echo "Usage: php create_admin.php email password \"Full Name\"\n";
    exit(1);
}
$email = $argv[1];
$password = $argv[2];
$name = $argv[3];

require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,"admin")');
try {
    $stmt->execute([$name, $email, $hash]);
    echo "Admin user created.\n";
} catch (Exception $e) {
    echo "Failed to create admin: " . $e->getMessage() . "\n";
}
