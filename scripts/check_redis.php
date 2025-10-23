<?php
/**
 * Redis Installation Check Script
 * Run this script from command line: php scripts/check_redis.php
 * Or via browser: http://localhost/backoffice_system/scripts/check_redis.php
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) echo "<pre>\n";

echo "Redis Installation Check\n";
echo "=====================\n\n";

// 1. Check PHP Redis Extensions
echo "1. Checking PHP Redis Extensions:\n";
echo "--------------------------------\n";
if (extension_loaded('redis')) {
    echo "✓ phpredis extension is installed (version: " . phpversion('redis') . ")\n";
} else {
    echo "✗ phpredis extension is not installed\n";
    echo "  To install:\n";
    echo "  - Windows: Uncomment 'extension=redis' in php.ini\n";
    echo "  - Linux: sudo apt-get install php-redis\n";
    echo "  - macOS: brew install php-redis\n";
}

// 2. Check Predis via Composer
echo "\n2. Checking Predis Installation:\n";
echo "------------------------------\n";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Predis\Client')) {
        echo "✓ Predis is installed via Composer\n";
    } else {
        echo "✗ Predis is not installed\n";
        echo "  To install: composer require predis/predis\n";
    }
} else {
    echo "✗ Composer autoload not found\n";
    echo "  Run 'composer install' in the project root\n";
}

// 3. Check Redis Server
echo "\n3. Checking Redis Server:\n";
echo "----------------------\n";

// Try phpredis first
$serverResponding = false;
if (extension_loaded('redis') && class_exists('Redis')) {
    try {
        $cls = 'Redis';
        $redis = new $cls();
        $redis->connect('127.0.0.1', 6379, 0.5);
        $pong = $redis->ping();
        echo "✓ Redis server is running (using phpredis)\n";
        echo "  Response: " . ($pong === "+PONG" || $pong === true ? "PONG" : $pong) . "\n";
        $serverResponding = true;
    } catch (Exception $e) {
        echo "✗ Could not connect to Redis server with phpredis\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

// Try Predis if phpredis failed
if (!$serverResponding && class_exists('Predis\Client')) {
    try {
        $redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'parameters' => ['timeout' => 0.5]
        ]);
        $pong = $redis->ping();
        echo "✓ Redis server is running (using Predis)\n";
        echo "  Response: " . $pong . "\n";
        $serverResponding = true;
    } catch (Exception $e) {
        echo "✗ Could not connect to Redis server with Predis\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

if (!$serverResponding) {
    echo "\nRedis Server Installation Instructions:\n";
    echo "-----------------------------------\n";
    echo "Windows:\n";
    echo "1. Download Redis for Windows: https://github.com/microsoftarchive/redis/releases\n";
    echo "2. Run the MSI installer\n";
    echo "3. Start Redis server:\n";
    echo "   - Via Services: Start 'Redis' service in Windows Services\n";
    echo "   - Or Command Line: 'redis-server'\n";
    echo "\nDocker:\n";
    echo "docker run --name redis -p 6379:6379 -d redis\n";
    echo "\nLinux:\n";
    echo "sudo apt-get install redis-server\n";
    echo "sudo systemctl start redis-server\n";
    echo "\nmacOS:\n";
    echo "brew install redis\n";
    echo "brew services start redis\n";
}

// 4. Check Config
echo "\n4. Checking Redis Configuration:\n";
echo "-----------------------------\n";
$config = require __DIR__ . '/../config/config.php';
if (isset($config['redis'])) {
    echo "✓ Redis configuration found in config.php\n";
    echo "  Host: " . ($config['redis']['host'] ?? '127.0.0.1') . "\n";
    echo "  Port: " . ($config['redis']['port'] ?? '6379') . "\n";
    echo "  Enabled: " . (($config['redis']['enabled'] ?? false) ? 'true' : 'false') . "\n";
} else {
    echo "✗ Redis configuration not found in config.php\n";
    echo "  Add the following to config.php:\n";
    echo "  'redis' => [\n";
    echo "      'enabled' => true,\n";
    echo "      'host' => '127.0.0.1',\n";
    echo "      'port' => 6379\n";
    echo "  ]\n";
}

if (!$isCli) echo "</pre>";