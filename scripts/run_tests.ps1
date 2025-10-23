param(
    [switch]$enableTests = $false
)

$env:TEST_ALLOW_LOGIN = if ($enableTests) { "true" } else { "" }
$env:APP_ENV = if ($enableTests) { "testing" } else { "" }

Write-Host "Starting PHP server..."
if ($enableTests) {
    Write-Host "Starting PHP server (with test endpoints enabled)..."
    Start-Process -NoNewWindow powershell -ArgumentList "php -S 127.0.0.1:8000 scripts/test_server.php"
} else {
    Write-Host "Starting PHP server (with test endpoints disabled)..."
    Start-Process -NoNewWindow powershell -ArgumentList "php -S 127.0.0.1:8000 -t public/"
}
Start-Sleep -Seconds 2  # Give server time to start

Write-Host "Running tests..."
Write-Host "Environment:"
Write-Host "TEST_ALLOW_LOGIN=$env:TEST_ALLOW_LOGIN"
Write-Host "APP_ENV=$env:APP_ENV"

$env:TEST_ALLOW_LOGIN = if ($enableTests) { "true" } else { "false" }
$env:APP_ENV = if ($enableTests) { "testing" } else { "" }
php scripts/role_test_new.php 'http://127.0.0.1:8000'
$testResult = $LASTEXITCODE

Write-Host "Stopping PHP server..."
Get-Process -Name php | Where-Object {$_.CommandLine -like "*127.0.0.1:8000*"} | Stop-Process

exit $testResult