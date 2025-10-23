# Test health token rotation with overlap window
# Usage: ./test_token_rotation.ps1 [overlap_seconds]
param(
    [int]$overlapSeconds = 10  # Use small window for test
)

$ErrorActionPreference = 'Stop'
$baseUrl = 'http://localhost/backoffice_system/public'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpScript = Join-Path $scriptDir 'rotate_health_token.php'

function Get-CurrentToken {
    $tokenFile = Join-Path $scriptDir '../var/health_token.json'
    if (Test-Path $tokenFile) {
        $store = Get-Content $tokenFile | ConvertFrom-Json
        return $store.current.token
    }
    return $null
}

function Test-Health {
    param([string]$token)
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/health?token=$token" -Method Get -UseBasicParsing
        return $true
    } catch {
        return $false
    }
}

Write-Host "Testing health token rotation with ${overlapSeconds}s overlap window..." -ForegroundColor Cyan

# Initial rotation to get a token
Write-Host "`nStep 1: Initial rotation" -ForegroundColor Yellow
php $phpScript
$token1 = Get-CurrentToken
Write-Host "Got token: $token1"

# Verify initial token works
Write-Host "`nStep 2: Verify initial token" -ForegroundColor Yellow
$works = Test-Health -token $token1
Write-Host "Initial token works: $works"

# Rotate to new token
Write-Host "`nStep 3: Rotate to new token" -ForegroundColor Yellow
php $phpScript
$token2 = Get-CurrentToken
Write-Host "New token: $token2"
Write-Host "Previous token should still work for ${overlapSeconds}s"

# Test both tokens work initially
Write-Host "`nStep 4: Test both tokens immediately after rotation" -ForegroundColor Yellow
$newWorks = Test-Health -token $token2
$oldWorks = Test-Health -token $token1
Write-Host "New token works: $newWorks"
Write-Host "Old token works: $oldWorks"

# Wait and test at intervals until old token expires
Write-Host "`nStep 5: Testing previous token expiry..." -ForegroundColor Yellow
$start = Get-Date
$interval = [Math]::Min(2, [Math]::Floor($overlapSeconds / 4))
$elapsed = 0

while ($elapsed -lt ($overlapSeconds + 5)) {
    $oldWorks = Test-Health -token $token1
    $status = if ($oldWorks) { "still valid" } else { "expired" }
    Write-Host "Previous token after ${elapsed}s: $status"
    
    if (-not $oldWorks) {
        Write-Host "`nPrevious token expired after ${elapsed}s (expected ~${overlapSeconds}s)" -ForegroundColor Green
        break
    }
    
    Start-Sleep -Seconds $interval
    $elapsed = [Math]::Floor(((Get-Date) - $start).TotalSeconds)
}

# Final verification
Write-Host "`nStep 6: Final verification" -ForegroundColor Yellow
$newWorks = Test-Health -token $token2
$oldWorks = Test-Health -token $token1
Write-Host "Current token works: $newWorks"
Write-Host "Previous token works: $oldWorks (should be false)"

Write-Host "`nTest complete!" -ForegroundColor Cyan