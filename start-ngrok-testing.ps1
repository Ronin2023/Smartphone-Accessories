# Ngrok Testing Helper Script
# Automatically starts ngrok and runs clean URL tests

Write-Host @"
╔════════════════════════════════════════════════════════════════════╗
║              Ngrok Clean URLs Testing Helper                       ║
║              TechCompare Smartphone Accessories                    ║
╚════════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor Cyan

# Check if ngrok is installed
Write-Host "`nChecking for ngrok installation..." -ForegroundColor Yellow
$ngrokPath = Get-Command ngrok -ErrorAction SilentlyContinue

if (!$ngrokPath) {
    Write-Host "❌ Ngrok not found!" -ForegroundColor Red
    Write-Host @"
    
Please install ngrok:
1. Download from: https://ngrok.com/download
2. Extract to a folder in PATH (e.g., C:\Program Files\ngrok\)
3. Sign up at ngrok.com and get your authtoken
4. Run: ngrok config add-authtoken YOUR_AUTH_TOKEN

Alternative (using Chocolatey):
choco install ngrok

"@ -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Ngrok found at: $($ngrokPath.Source)" -ForegroundColor Green

# Check if authtoken is configured
Write-Host "`nChecking ngrok configuration..." -ForegroundColor Yellow
$configPath = "$env:USERPROFILE\.ngrok2\ngrok.yml"
if (Test-Path $configPath) {
    $config = Get-Content $configPath -Raw
    if ($config -match "authtoken:") {
        Write-Host "✅ Ngrok is configured with authtoken" -ForegroundColor Green
    } else {
        Write-Host "⚠️  No authtoken found in config" -ForegroundColor Yellow
        Write-Host "Run: ngrok config add-authtoken YOUR_AUTH_TOKEN" -ForegroundColor Cyan
    }
} else {
    Write-Host "⚠️  Ngrok config not found" -ForegroundColor Yellow
    Write-Host "Run: ngrok config add-authtoken YOUR_AUTH_TOKEN" -ForegroundColor Cyan
}

# Check if Laragon is running
Write-Host "`nChecking Laragon Apache status..." -ForegroundColor Yellow
$apacheProcess = Get-Process -Name httpd -ErrorAction SilentlyContinue
if ($apacheProcess) {
    Write-Host "✅ Apache is running" -ForegroundColor Green
} else {
    Write-Host "❌ Apache is not running!" -ForegroundColor Red
    Write-Host "Please start Laragon before proceeding." -ForegroundColor Yellow
    exit 1
}

# Test localhost first
Write-Host "`nTesting localhost first..." -ForegroundColor Yellow
try {
    $localTest = Invoke-WebRequest -Uri "http://localhost/Smartphone-Accessories/index" -UseBasicParsing -TimeoutSec 5
    Write-Host "✅ Localhost is working (HTTP $($localTest.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "❌ Localhost test failed: $_" -ForegroundColor Red
    Write-Host "Please ensure Laragon is running and project is accessible." -ForegroundColor Yellow
    exit 1
}

# Prompt to start ngrok
Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
Write-Host "Ready to start ngrok tunnel!" -ForegroundColor Green
Write-Host $('=' * 70) -ForegroundColor Cyan
Write-Host @"

Ngrok will create a public URL for your localhost server.
This allows you to:
  ✅ Test on mobile devices
  ✅ Share with others
  ✅ Test HTTPS functionality
  ✅ Test external webhooks/APIs

Press Enter to start ngrok, or Ctrl+C to cancel...
"@ -ForegroundColor Yellow

Read-Host

# Start ngrok in background
Write-Host "`nStarting ngrok tunnel..." -ForegroundColor Cyan
Write-Host "Please wait while ngrok initializes..." -ForegroundColor Yellow

$ngrokJob = Start-Job -ScriptBlock {
    & ngrok http 80 --host-header=localhost
}

# Wait for ngrok to initialize
Start-Sleep -Seconds 3

# Get ngrok URL from API
Write-Host "`nFetching ngrok URL..." -ForegroundColor Yellow
try {
    $ngrokApi = Invoke-RestMethod -Uri "http://localhost:4040/api/tunnels" -Method Get -TimeoutSec 5
    $publicUrl = $ngrokApi.tunnels[0].public_url
    
    if ($publicUrl) {
        Write-Host "`n$('=' * 70)" -ForegroundColor Green
        Write-Host "✅ Ngrok tunnel is active!" -ForegroundColor Green
        Write-Host $('=' * 70) -ForegroundColor Green
        Write-Host "`nPublic URL: $publicUrl" -ForegroundColor Cyan
        Write-Host "Dashboard: http://localhost:4040" -ForegroundColor Cyan
        
        # Test ngrok URL
        Write-Host "`nTesting ngrok URL..." -ForegroundColor Yellow
        $ngrokUrl = $publicUrl.Replace('http://', 'https://') + "/Smartphone-Accessories"
        
        try {
            $ngrokTest = Invoke-WebRequest -Uri "$ngrokUrl/index" -UseBasicParsing -TimeoutSec 10
            Write-Host "✅ Ngrok URL is accessible (HTTP $($ngrokTest.StatusCode))" -ForegroundColor Green
            
            # Run comprehensive tests
            Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
            Write-Host "Running comprehensive clean URL tests..." -ForegroundColor Cyan
            Write-Host $('=' * 70) -ForegroundColor Cyan
            
            & .\test-clean-urls.ps1 -Environment ngrok -NgrokUrl $ngrokUrl
            
        } catch {
            Write-Host "⚠️  Warning: Initial ngrok test failed: $_" -ForegroundColor Yellow
            Write-Host "The tunnel may need a moment to warm up. Try manually accessing:" -ForegroundColor Yellow
            Write-Host "  $ngrokUrl/index" -ForegroundColor Cyan
        }
        
        # Keep ngrok running
        Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
        Write-Host "Ngrok Tunnel Information" -ForegroundColor Cyan
        Write-Host $('=' * 70) -ForegroundColor Cyan
        Write-Host @"

Public URL:    $publicUrl/Smartphone-Accessories
Dashboard:     http://localhost:4040
Status:        Active ✅

Test URLs:
  Home:        $publicUrl/Smartphone-Accessories/index
  Contact:     $publicUrl/Smartphone-Accessories/contact
  Products:    $publicUrl/Smartphone-Accessories/products
  Compare:     $publicUrl/Smartphone-Accessories/compare
  About:       $publicUrl/Smartphone-Accessories/about

The tunnel will remain active until you press Ctrl+C or close this window.
"@ -ForegroundColor White
        
        Write-Host "`nPress Ctrl+C to stop ngrok tunnel..." -ForegroundColor Yellow
        
        # Keep running
        Wait-Job -Job $ngrokJob
        
    } else {
        throw "Could not retrieve public URL from ngrok API"
    }
    
} catch {
    Write-Host "`n❌ Error: Failed to get ngrok URL" -ForegroundColor Red
    Write-Host "Error details: $_" -ForegroundColor Red
    Write-Host @"
    
Troubleshooting:
1. Check if ngrok is running: http://localhost:4040
2. Verify authtoken is configured
3. Check firewall settings
4. Try running manually: ngrok http 80 --host-header=localhost

"@ -ForegroundColor Yellow
    
    # Cleanup
    if ($ngrokJob) {
        Stop-Job -Job $ngrokJob -ErrorAction SilentlyContinue
        Remove-Job -Job $ngrokJob -ErrorAction SilentlyContinue
    }
    exit 1
}

# Cleanup on exit
Write-Host "`nStopping ngrok..." -ForegroundColor Yellow
Stop-Job -Job $ngrokJob -ErrorAction SilentlyContinue
Remove-Job -Job $ngrokJob -ErrorAction SilentlyContinue
Write-Host "✅ Ngrok stopped" -ForegroundColor Green
