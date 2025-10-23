# Quick test for health token rotation with overlap window
# Usage: ./test_rotation_minimal.ps1

$baseUrl = 'http://localhost/backoffice_system/public'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Helper to extract token from rotate_health_token.php output
function Get-TokenFromOutput {
    param([string]$output)
    if ($output -match 'token: ([a-f0-9]+)') {
        return $matches[1]
    }
    return $null
}

# Test health endpoint with token
function Test-Health {
    param([string]$token)
    try {
        Write-Host "Calling health check with token: $token" -ForegroundColor Gray
        $response = Invoke-RestMethod -Uri "$baseUrl/health?token=$token" -Method Get -UseBasicParsing -ErrorAction Stop
        Write-Host "Response: $($response | ConvertTo-Json -Compress)" -ForegroundColor Gray
        return $true
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq 429) {
            Write-Host "Rate limited - waiting 30 seconds..." -ForegroundColor Yellow
            Start-Sleep -Seconds 30
            # Try again
            try {
                $response = Invoke-RestMethod -Uri "$baseUrl/health?token=$token" -Method Get -UseBasicParsing -ErrorAction Stop
                Write-Host "Response: $($response | ConvertTo-Json -Compress)" -ForegroundColor Gray
                return $true
            } catch {
                Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
                return $false
            }
        } else {
            Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
            return $false
        }
    }
}

Write-Host "`n=== Quick Health Token Rotation Test ===" -ForegroundColor Cyan

# Check if health endpoint is accessible
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/health" -Method Get -UseBasicParsing -ErrorAction Stop
    Write-Host "Health endpoint is accessible (no token required)" -ForegroundColor Green
    Write-Host "Response: $($response | ConvertTo-Json)" -ForegroundColor Gray
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 429) {
        Write-Host "Health endpoint accessible but rate limited. Waiting 30 seconds..." -ForegroundColor Yellow
        Start-Sleep -Seconds 30
    } elseif ($statusCode -eq 401) {
        Write-Host "Health endpoint is accessible (token required)" -ForegroundColor Green
    } else {
        Write-Host "ERROR: Health endpoint not accessible at $baseUrl/health (HTTP $statusCode)" -ForegroundColor Red
        Write-Host "Make sure Apache is running and the endpoint is available" -ForegroundColor Red
        exit 1
    }
}

# Show current token store
$tokenFile = Join-Path $scriptDir '../var/health_token.json'
if (Test-Path $tokenFile) {
    Write-Host "`nCurrent token store:" -ForegroundColor Yellow
    Get-Content $tokenFile
}

# Get first token with 5 second lifetime
Write-Host "`n1. Getting initial token..." -ForegroundColor Yellow
$output = php $scriptDir/rotate_health_token.php 5
$token1 = Get-TokenFromOutput $output
if (-not $token1) {
    Write-Host "Failed to get initial token" -ForegroundColor Red
    exit 1
}
Write-Host "Initial token: $token1"

# Check it works
$works = Test-Health -token $token1
Write-Host "Initial token works: $works"

# Show updated token store
Write-Host "`nToken store after first rotation:" -ForegroundColor Yellow
if (Test-Path $tokenFile) {
    Get-Content $tokenFile
}

# Get second token (rotate) with 5 second lifetime
Write-Host "`n2. Rotating to new token..." -ForegroundColor Yellow
$output = php $scriptDir/rotate_health_token.php 5
$token2 = Get-TokenFromOutput $output
Write-Host "New token: $token2"

# Show final token store
Write-Host "`nToken store after second rotation:" -ForegroundColor Yellow
if (Test-Path $tokenFile) {
    Get-Content $tokenFile
}

# Test both tokens during overlap window
Write-Host "`n3. Testing both tokens during overlap window:" -ForegroundColor Yellow
$newWorksOverlap = Test-Health -token $token2
$oldWorksOverlap = Test-Health -token $token1
Write-Host "New token works (overlap): $newWorksOverlap"
Write-Host "Previous token works (overlap): $oldWorksOverlap (should work)"

# Let token expire completely
Write-Host "`nWaiting 6 seconds for tokens to expire..." -ForegroundColor Yellow
Start-Sleep -Seconds 6

# Test both tokens after overlap expiry
Write-Host "`n4. Testing tokens after overlap expired:" -ForegroundColor Yellow
$newWorksAfter = Test-Health -token $token2
$oldWorksAfter = Test-Health -token $token1
Write-Host "New token works (after): $newWorksAfter (should NOT work - expired)"
Write-Host "Previous token works (after): $oldWorksAfter (should NOT work)"

Write-Host "`nTest complete!" -ForegroundColor Cyan