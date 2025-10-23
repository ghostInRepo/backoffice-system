<?php
// Global config
return [
    'app' => [
        'name' => 'Travel Agency Backoffice',
        'base_url' => '/backoffice_system/public'
    ],
    'auth' => [
        'session_timeout' => 1800 // seconds
    ]
    , 'mail' => [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'from_email' => 'no-reply@example.com',
        'from_name' => 'Travel Backoffice'
    ]
    , 'redis' => [
        'enabled' => true,  // Enable Redis integration
        'driver' => 'predis', // Force using Predis instead of phpredis
        'host' => '127.0.0.1',
        'port' => 6379,
        'auth' => ''
    ]
    , 'health' => [
        // Optional token to restrict health endpoints. Leave empty for public access.
        'token' => '',
        // Rotation settings
        'rotate' => true,
        // Interval in seconds (default 24h)
        'rotate_interval' => 86400,
        // If true, ignore static 'token' and always use rotating token
        'force_rotate' => true
    ]
    , 'tests' => [
        // When true, enables test-only endpoints (e.g. /__test_login and /__role_check).
        // TEMPORARILY ENABLED FOR TESTING
        'allow_test_login' => true,
        // List of allowed IP ranges for test endpoints (in addition to localhost/CLI).
        'allowed_ci_ips' => [
            '127.0.0.1/32',
            '::1'
        ]
    ]
];
