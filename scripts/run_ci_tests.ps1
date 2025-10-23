# CI test runner for Windows (PowerShell)
# - Sets env vars required for test endpoints
# - Verifies database connection and schema
# - Runs PHP test scripts and fails on any error

param(
    [string]$BaseUrl = 'http://localhost/backoffice_system/public',
    [string]$DbHost = $env:TEST_DB_HOST,
    [string]$DbName = $env:TEST_DB_NAME,
    [string]$DbUser = $env:TEST_DB_USER,
    [string]$DbPass = $env:TEST_DB_PASS
)

Write-Host "Running CI tests against $BaseUrl"

# Export environment variables for PHP CLI and web server tests
$env:TEST_ALLOW_LOGIN = 'true'
$env:APP_ENV = 'testing'

if (-not $env:TEST_DB_HOST) {
    $env:TEST_DB_HOST = '127.0.0.1'
    $env:TEST_DB_NAME = 'travel_backoffice_test'
    $env:TEST_DB_USER = 'root'
    $env:TEST_DB_PASS = ''
}

# Ensure PHP is available
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Error "php not found in PATH"
    exit 2
}

Push-Location $PSScriptRoot/..\

# Check database connection and schema
Write-Host "Checking database..."
$db = & php scripts/check_test_db.php 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error "Database check failed"
    Write-Error $db
    Pop-Location
    exit $LASTEXITCODE
}
Write-Host $db

# Run role tests
Write-Host "Running role tests..."
$role = & php scripts/role_test.php 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error "Role tests failed"
    Write-Error $role
    Pop-Location
    exit $LASTEXITCODE
}
Write-Host $role

# Run simple test
Write-Host "Running simple test..."
$simple = & php scripts/simple_test.php 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error "Simple test failed"
    Write-Error $simple
    Pop-Location
    exit $LASTEXITCODE
}
Write-Host $simple

# Verify session cleanup
Write-Host "Checking session cleanup..."
$sessions = & mysql -N -h $env:TEST_DB_HOST -u $env:TEST_DB_USER -p$env:TEST_DB_PASS $env:TEST_DB_NAME -e "SELECT COUNT(*) FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
if ($sessions -gt 0) {
    Write-Warning "Found $sessions stale sessions"
}

Pop-Location
Write-Host "All CI tests passed"
exit 0
