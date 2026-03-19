# test-rate-limit.ps1
# Tenant-aware rate limiting manuel test scripti
# Kullanım: .\scripts\test-rate-limit.ps1 -TenantId 1 -Endpoint "http://localhost:8000/api/projects" -MaxRequests 65

param(
    [int]$TenantId = 1,
    [string]$Endpoint = "http://localhost:8000/api/projects",
    [int]$MaxRequests = 65
)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Tenant-Aware Rate Limit Test" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tenant ID: $TenantId"
Write-Host "Endpoint:  $Endpoint"
Write-Host "Max Requests: $MaxRequests"
Write-Host ""
Write-Host "Starting test..." -ForegroundColor Yellow
Write-Host ""

$rateLimitHit = $false
$successCount = 0
$failCount = 0

for ($i = 1; $i -le $MaxRequests; $i++) {
    try {
        $response = Invoke-WebRequest -Uri $Endpoint -Headers @{"X-Tenant-ID" = $TenantId} -SkipHttpErrorCheck -TimeoutSec 10
        $status = $response.StatusCode
        
        if ($status -eq 429) {
            $retryAfter = $response.Headers['Retry-After']
            $remaining = $response.Headers['X-RateLimit-Remaining']
            
            Write-Host "Request $i -> " -NoNewline
            Write-Host "429 RATE LIMITED" -ForegroundColor Red
            Write-Host "  Retry-After: $retryAfter seconds" -ForegroundColor Yellow
            Write-Host "  X-RateLimit-Remaining: $remaining" -ForegroundColor Yellow
            
            $rateLimitHit = $true
            $failCount++
            
            # Rate limit hit, test basarili
            break
        }
        elseif ($status -eq 200) {
            Write-Host "Request $i -> $status OK" -ForegroundColor Green
            $successCount++
        }
        elseif ($status -eq 400) {
            Write-Host "Request $i -> 400 Bad Request (Tenant header eksik?)" -ForegroundColor Red
            $failCount++
            break
        }
        elseif ($status -eq 403) {
            Write-Host "Request $i -> 403 Forbidden (Tenant inactive?)" -ForegroundColor Red
            $failCount++
            break
        }
        elseif ($status -eq 404) {
            Write-Host "Request $i -> 404 Not Found (Tenant bulunamadi?)" -ForegroundColor Red
            $failCount++
            break
        }
        else {
            Write-Host "Request $i -> $status" -ForegroundColor Yellow
            $failCount++
        }
    }
    catch {
        Write-Host "Request $i -> ERROR: $($_.Exception.Message)" -ForegroundColor Red
        $failCount++
        break
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Test Summary" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Successful requests: $successCount" -ForegroundColor Green
Write-Host "Failed requests: $failCount" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })

if ($rateLimitHit) {
    Write-Host ""
    Write-Host "Rate limit reached at request $i" -ForegroundColor Yellow
    Write-Host "This confirms rate limiting is working correctly!" -ForegroundColor Green
}
else {
    Write-Host ""
    Write-Host "Rate limit was NOT reached after $MaxRequests requests" -ForegroundColor Yellow
    Write-Host "Try increasing MaxRequests or check rate limit configuration" -ForegroundColor Yellow
}
