# Smoke test: simulate register -> login -> read OTP from mail.log -> verify 2FA -> access /dashboard
param(
    [string]$BaseUrl = 'http://localhost/backoffice_system/public',
    [string]$TestEmail = 'smoketest@example.local',
    [string]$TestName = 'Smoke Test',
    [string]$TestPass = 'TestPass123!'
)

Write-Host "Base URL: $BaseUrl"

# Helper: post form and return cookies and body
function Invoke-FormPost($url, $form, [ref]$cookies) {
    $resp = Invoke-WebRequest -Uri $url -Method Post -Body $form -SessionVariable session -UseBasicParsing -ErrorAction Stop
    $cookies.Value = $session.Cookies
    return $resp.Content
}

# 1) Try to register (ignore errors if user exists)
try {
    Write-Host "Registering user $TestEmail ..."
    $regForm = @{ name=$TestName; email=$TestEmail; password=$TestPass; role='agent' }
    $r = Invoke-WebRequest -Uri "$BaseUrl/register" -Method Post -Body $regForm -UseBasicParsing -ErrorAction Stop
    Write-Host "Register response status: $($r.StatusCode)"
} catch {
    Write-Host "Register likely failed or user exists: $($_.Exception.Message)" -ForegroundColor Yellow
}

# 2) Login (POST) to initiate OTP
try {
    Write-Host "Logging in..."
    # Get login page first to collect any cookies
    $loginPage = Invoke-WebRequest -Uri "$BaseUrl/login" -UseBasicParsing -ErrorAction Stop
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    foreach ($c in $loginPage.Cookies) { $session.Cookies.Add($c) }

    $loginForm = @{ email=$TestEmail; password=$TestPass }
    $loginResp = Invoke-WebRequest -Uri "$BaseUrl/login" -Method Post -Body $loginForm -WebSession $session -UseBasicParsing -ErrorAction Stop
    Write-Host "Login POST returned status: $($loginResp.StatusCode)"
} catch {
    Write-Host "Login POST failed: $($_.Exception.Message)" -ForegroundColor Red; exit 1
}

# 3) Read latest OTP from var/logs/mail.log
$logPath = "$(Split-Path -Path $PSScriptRoot -Parent)\var\logs\mail.log"
if (-Not (Test-Path $logPath)) {
    Write-Host "Mail log not found at $logPath" -ForegroundColor Red; exit 1
}

# Get last OTP entry
$lines = Get-Content $logPath -ErrorAction Stop
# Find last line that contains 'Your OTP code' or a 6-digit number
$otp = $null
for ($i = $lines.Length - 1; $i -ge 0; $i--) {
    $line = $lines[$i]
    if ($line -match '\b(\d{6})\b') { $otp = $matches[1]; break }
}
if (-not $otp) { Write-Host "Could not find OTP in mail.log" -ForegroundColor Red; exit 1 }
Write-Host "Found OTP: $otp"

# 4) Submit OTP to /verify-2fa using same session
try {
    Write-Host "Submitting OTP..."
    $verifyForm = @{ otp=$otp }
    $verifyResp = Invoke-WebRequest -Uri "$BaseUrl/verify-2fa" -Method Post -Body $verifyForm -WebSession $session -UseBasicParsing -ErrorAction Stop
    Write-Host "Verify response status: $($verifyResp.StatusCode)"
    Write-Host "Session cookies after verify:"
    foreach ($c in $session.Cookies) { Write-Host " - $($c.Name) = $($c.Value)" }
} catch {
    # Some frameworks redirect with 302 and Invoke-WebRequest might throw; check final location via session
    Write-Host "Verify POST encountered an issue: $($_.Exception.Message)" -ForegroundColor Yellow
}

# 5) Access protected page /dashboard
try {
    $dash = Invoke-WebRequest -Uri "$BaseUrl/dashboard" -WebSession $session -UseBasicParsing -ErrorAction Stop
    Write-Host "Dashboard status: $($dash.StatusCode)"
    if ($null -ne $dash.Content -and $dash.Content.Length -gt 0) {
        $snippet = $dash.Content.Substring(0,[Math]::Min(800,$dash.Content.Length))
        Write-Host "--- Dashboard Snippet ---"
        Write-Host $snippet
    } else {
        Write-Host "No content available in response. Showing response headers and raw content if present:" -ForegroundColor Yellow
        if ($dash.Headers) { $dash.Headers.GetEnumerator() | ForEach-Object { Write-Host "$_" } }
        if ($dash.RawContent) { Write-Host "--- RawContent ---"; Write-Host $dash.RawContent }
    }
} catch {
    Write-Host "Failed to fetch dashboard: $($_.Exception.Message)" -ForegroundColor Red; exit 1
}

Write-Host "Smoke test completed." -ForegroundColor Green
