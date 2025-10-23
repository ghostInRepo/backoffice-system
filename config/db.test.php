<?php
// Test database configuration
return (function(){
    // Allow override via env vars for CI
    $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
    $db   = getenv('TEST_DB_NAME') ?: 'travel_backoffice_test';
    $user = getenv('TEST_DB_USER') ?: 'root';
    $pass = getenv('TEST_DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        throw new RuntimeException('Test database connection failed: ' . $e->getMessage());
    }
})();