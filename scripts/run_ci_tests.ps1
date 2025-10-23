# CI test runner for Windows (PowerShell)
# - Sets env vars required for test endpoints
# - Runs PHP test scripts and fails on any error

param(
    [string]$BaseUrl = 'http://localhost/backoffice_system/public'
)

Write-Host "Running CI tests against $BaseUrl"

# Export environment variables for PHP CLI and web server tests
$env:TEST_ALLOW_LOGIN = 'true'
$env:APP_ENV = 'testing'

# Ensure PHP is available
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Error "php not found in PATH"
    exit 2
}

Push-Location $PSScriptRoot/..\

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

Pop-Location
Write-Host "All CI tests passed"
exit 0
