<?php
/**
 * PHP Version Compatibility Check
 * Runs before tests to ensure code compatibility with current PHP version
 */

echo "=== PHP VERSION COMPATIBILITY CHECK ===\n";

$requiredExtensions = [
    'pdo_mysql' => '>=7.4',
    'json' => '>=7.4',
    'mbstring' => '>=7.4'
];

// Optional extensions with version requirements
$optionalExtensions = [
    'redis' => [
        'version' => '>=7.4',
        'reason' => 'Required for session handling and caching'
    ]
];

// PHP version specific checks
$versionChecks = [
    '7.4' => [
        'features' => [
            'Arrow functions' => fn() => true,
            'Typed properties' => function() {
                $obj = new class { public string $test; };
                $obj->test = '';
                return true;
            },
            'Null coalescing assignment' => function() {
                $array = []; $array['key'] ??= 'value'; return true;
            }
        ]
    ],
    '8.0' => [
        'features' => [
            'Named arguments' => function() {
                $closure = function(string $name) { return $name; };
                return $closure(name: 'test');
            },
            'Constructor property promotion' => function() {
                return new class('test') { public function __construct(public string $name) {} };
            }
        ]
    ],
    '8.1' => [
        'features' => [
            'Enums' => function() {
                if (PHP_VERSION_ID >= 80100) {
                    eval('enum Test { case A; }');
                }
                return true;
            }
        ]
    ],
    '8.2' => [
        'features' => [
            'Readonly classes' => function() {
                if (PHP_VERSION_ID >= 80200) {
                    eval('readonly class Test { public string $prop = "test"; }');
                }
                return true;
            }
        ]
    ]
];

$errors = [];
$warnings = [];

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = "PHP version must be at least 7.4.0";
}

// Check required extensions
echo "\nChecking required extensions...\n";
foreach ($requiredExtensions as $ext => $version) {
    if (!extension_loaded($ext)) {
        $errors[] = "Required extension not loaded: {$ext}";
    } else {
        echo "✓ {$ext}\n";
    }
}

// Check optional extensions
echo "\nChecking optional extensions...\n";
foreach ($optionalExtensions as $ext => $config) {
    if (!extension_loaded($ext)) {
        $warnings[] = "Optional extension not loaded: {$ext} ({$config['reason']})";
    } else {
        echo "✓ {$ext}\n";
    }
}

// Run version-specific feature checks
echo "\nChecking PHP version features...\n";
foreach ($versionChecks as $version => $config) {
    if (version_compare(PHP_VERSION, $version, '>=')) {
        foreach ($config['features'] as $feature => $test) {
            try {
                $test();
                echo "✓ {$feature} (PHP {$version})\n";
            } catch (Throwable $e) {
                $errors[] = "Feature '{$feature}' failed: " . $e->getMessage();
            }
        }
    }
}

// Display results
if (!empty($warnings)) {
    echo "\nWarnings:\n";
    foreach ($warnings as $warning) {
        echo "⚠ {$warning}\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "✗ {$error}\n";
    }
    exit(1);
}

echo "\nAll compatibility checks passed!\n";