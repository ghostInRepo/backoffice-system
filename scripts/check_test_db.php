<?php
// Test database connection and schema verification
echo "=== DATABASE CHECK ===\n";

// Load test database connection
$db = require __DIR__ . '/../config/db.test.php';

// Check connection
echo "Checking database connection...\n";
try {
    $db->query('SELECT 1');
    echo "Database connection OK\n";
} catch (Exception $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Check required tables exist
$requiredTables = ['users', 'roles', 'sessions'];
echo "Checking required tables...\n";

$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requiredTables as $table) {
    if (!in_array($table, $tables)) {
        fwrite(STDERR, "Missing required table: $table\n");
        exit(2);
    }
}
echo "Required tables OK\n";

// Check users table schema
echo "Checking users table schema...\n";
$stmt = $db->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$required = ['id', 'email', 'password', 'name', 'role'];

foreach ($required as $column) {
    if (!in_array($column, $columns)) {
        fwrite(STDERR, "Missing required column in users table: $column\n");
        exit(3);
    }
}
echo "Users table schema OK\n";

// Verify test user creation
echo "Verifying test user creation...\n";
$email = 'test.db.' . time() . '@example.com';
$stmt = $db->prepare('INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)');
try {
    $db->beginTransaction();
    $stmt->execute([$email, password_hash('TestPass123!', PASSWORD_DEFAULT), 'Test User', 'agent']);
    $id = $db->lastInsertId();
    $db->commit();
    
    // Clean up
    $db->exec("DELETE FROM users WHERE id = $id");
    echo "Test user creation OK\n";
} catch (Exception $e) {
    $db->rollBack();
    fwrite(STDERR, "Test user creation failed: " . $e->getMessage() . "\n");
    exit(4);
}

echo "All database checks passed\n";