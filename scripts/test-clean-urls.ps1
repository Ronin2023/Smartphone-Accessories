# Clean URLs Testing Script
# TechCompare Smartphone Accessories
# Tests clean URL functionality on localhost, ngrok, and production

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('localhost', 'ngrok', 'production', 'all')]
    [string]$Environment = 'localhost',
    
    [Parameter(Mandatory=$false)]
    [string]$NgrokUrl = '',
    
    [Parameter(Mandatory=$false)]
    [string]$ProductionUrl = ''
)

# Color codes
$colors = @{
    Success = 'Green'
    Error = 'Red'
    Info = 'Cyan'
    Warning = 'Yellow'
}

# Configuration
$localBase = "http://localhost/Smartphone-Accessories"

# Test pages
$testPages = @(
    @{ Name = 'Home'; Path = 'index'; ExpectedStatus = 200 }
    @{ Name = 'Contact'; Path = 'contact'; ExpectedStatus = 200 }
    @{ Name = 'Products'; Path = 'products'; ExpectedStatus = 200 }
    @{ Name = 'Compare'; Path = 'compare'; ExpectedStatus = 200 }
    @{ Name = 'About'; Path = 'about'; ExpectedStatus = 200 }
    @{ Name = 'Check Response'; Path = 'check-response'; ExpectedStatus = 200 }
    @{ Name = 'User Dashboard'; Path = 'user_dashboard'; ExpectedStatus = 200 }
    @{ Name = 'User Login'; Path = 'user_login'; ExpectedStatus = 200 }
)

# Test redirects (old .php URLs should redirect to clean URLs)
$testRedirects = @(
    @{ Name = 'Contact PHP → Contact'; Path = 'contact.php'; RedirectTo = 'contact'; ExpectedStatus = 301 }
    @{ Name = 'Products PHP → Products'; Path = 'products.php'; RedirectTo = 'products'; ExpectedStatus = 301 }
    @{ Name = 'About PHP → About'; Path = 'about.php'; RedirectTo = 'about'; ExpectedStatus = 301 }
)

# Test admin panel
$testAdmin = @(
    @{ Name = 'Admin Login'; Path = 'admin/index'; ExpectedStatus = 200 }
    @{ Name = 'Admin Dashboard'; Path = 'admin/dashboard'; ExpectedStatus = 200 }
)

# Test with query parameters
$testParams = @(
    @{ Name = 'Products with ID'; Path = 'products?id=1'; ExpectedStatus = 200 }
    @{ Name = 'Compare with IDs'; Path = 'compare?products=1,2,3'; ExpectedStatus = 200 }
)

function Write-ColorOutput {
    param([string]$Message, [string]$Color = 'White')
    Write-Host $Message -ForegroundColor $Color
}

function Test-URL {
    param(
        [string]$BaseUrl,
        [string]$Path,
        [int]$ExpectedStatus = 200,
        [bool]$FollowRedirect = $true
    )
    
    $url = "$BaseUrl/$Path"
    
    try {
        $params = @{
            Uri = $url
            Method = 'HEAD'
            UseBasicParsing = $true
            TimeoutSec = 10
        }
        
        if (!$FollowRedirect) {
            $params.MaximumRedirection = 0
            $params.ErrorAction = 'SilentlyContinue'
        }
        
        $response = Invoke-WebRequest @params
        $actualStatus = $response.StatusCode
        
        return @{
            Success = ($actualStatus -eq $ExpectedStatus)
            StatusCode = $actualStatus
            Headers = $response.Headers
            Error = $null
        }
    }
    catch {
        # For 3xx redirects, PowerShell throws an error
        if ($_.Exception.Response) {
            $actualStatus = [int]$_.Exception.Response.StatusCode
            return @{
                Success = ($actualStatus -eq $ExpectedStatus)
                StatusCode = $actualStatus
                Headers = $_.Exception.Response.Headers
                Error = $null
            }
        }
        
        return @{
            Success = $false
            StatusCode = 0
            Headers = $null
            Error = $_.Exception.Message
        }
    }
}

function Test-Environment {
    param([string]$BaseUrl, [string]$EnvironmentName)
    
    Write-ColorOutput "`n$('=' * 70)" $colors.Info
    Write-ColorOutput "Testing $EnvironmentName Environment" $colors.Info
    Write-ColorOutput "Base URL: $BaseUrl" $colors.Info
    Write-ColorOutput "$('=' * 70)" $colors.Info
    
    $totalTests = 0
    $passedTests = 0
    $failedTests = 0
    
    # Test main pages
    Write-ColorOutput "`n📄 Testing Main Pages..." $colors.Info
    foreach ($page in $testPages) {
        $totalTests++
        $result = Test-URL -BaseUrl $BaseUrl -Path $page.Path -ExpectedStatus $page.ExpectedStatus
        
        if ($result.Success) {
            Write-ColorOutput "  ✅ $($page.Name) ($($page.Path)) - $($result.StatusCode)" $colors.Success
            $passedTests++
        }
        else {
            Write-ColorOutput "  ❌ $($page.Name) ($($page.Path)) - Expected: $($page.ExpectedStatus), Got: $($result.StatusCode)" $colors.Error
            if ($result.Error) {
                Write-ColorOutput "     Error: $($result.Error)" $colors.Error
            }
            $failedTests++
        }
        Start-Sleep -Milliseconds 200
    }
    
    # Test redirects
    Write-ColorOutput "`n🔄 Testing Redirects (301 Permanent)..." $colors.Info
    foreach ($redirect in $testRedirects) {
        $totalTests++
        $result = Test-URL -BaseUrl $BaseUrl -Path $redirect.Path -ExpectedStatus $redirect.ExpectedStatus -FollowRedirect $false
        
        if ($result.Success) {
            Write-ColorOutput "  ✅ $($redirect.Name) - $($result.StatusCode) Redirect" $colors.Success
            $passedTests++
        }
        else {
            Write-ColorOutput "  ❌ $($redirect.Name) - Expected: $($redirect.ExpectedStatus), Got: $($result.StatusCode)" $colors.Error
            $failedTests++
        }
        Start-Sleep -Milliseconds 200
    }
    
    # Test query parameters
    Write-ColorOutput "`n🔗 Testing URLs with Query Parameters..." $colors.Info
    foreach ($param in $testParams) {
        $totalTests++
        $result = Test-URL -BaseUrl $BaseUrl -Path $param.Path -ExpectedStatus $param.ExpectedStatus
        
        if ($result.Success) {
            Write-ColorOutput "  ✅ $($param.Name) - $($result.StatusCode)" $colors.Success
            $passedTests++
        }
        else {
            Write-ColorOutput "  ❌ $($param.Name) - Expected: $($param.ExpectedStatus), Got: $($result.StatusCode)" $colors.Error
            $failedTests++
        }
        Start-Sleep -Milliseconds 200
    }
    
    # Test admin panel
    Write-ColorOutput "`n🔐 Testing Admin Panel..." $colors.Info
    foreach ($admin in $testAdmin) {
        $totalTests++
        $result = Test-URL -BaseUrl $BaseUrl -Path $admin.Path -ExpectedStatus $admin.ExpectedStatus
        
        if ($result.Success) {
            Write-ColorOutput "  ✅ $($admin.Name) - $($result.StatusCode)" $colors.Success
            $passedTests++
        }
        else {
            Write-ColorOutput "  ❌ $($admin.Name) - Expected: $($admin.ExpectedStatus), Got: $($result.StatusCode)" $colors.Error
            $failedTests++
        }
        Start-Sleep -Milliseconds 200
    }
    
    # Summary
    Write-ColorOutput "`n$('=' * 70)" $colors.Info
    Write-ColorOutput "Test Summary for $EnvironmentName" $colors.Info
    Write-ColorOutput "$('=' * 70)" $colors.Info
    Write-ColorOutput "Total Tests: $totalTests" $colors.Info
    Write-ColorOutput "Passed: $passedTests" $colors.Success
    Write-ColorOutput "Failed: $failedTests" $(if ($failedTests -eq 0) { $colors.Success } else { $colors.Error })
    Write-ColorOutput "Success Rate: $([math]::Round(($passedTests / $totalTests) * 100, 2))%" $(if ($failedTests -eq 0) { $colors.Success } else { $colors.Warning })
    
    return @{
        Total = $totalTests
        Passed = $passedTests
        Failed = $failedTests
        SuccessRate = ($passedTests / $totalTests) * 100
    }
}

# Main execution
Write-ColorOutput @"
╔════════════════════════════════════════════════════════════════════╗
║           TechCompare Clean URLs Testing Script                    ║
║           Testing .htaccess URL Rewriting                          ║
╚════════════════════════════════════════════════════════════════════╝
"@ $colors.Info

$allResults = @{}

switch ($Environment) {
    'localhost' {
        $allResults.Localhost = Test-Environment -BaseUrl $localBase -EnvironmentName "LOCALHOST"
    }
    'ngrok' {
        if ([string]::IsNullOrEmpty($NgrokUrl)) {
            Write-ColorOutput "`n❌ Error: Ngrok URL required. Use -NgrokUrl parameter." $colors.Error
            Write-ColorOutput "Example: .\test-clean-urls.ps1 -Environment ngrok -NgrokUrl 'https://abc123.ngrok.io'" $colors.Info
            exit 1
        }
        $allResults.Ngrok = Test-Environment -BaseUrl $NgrokUrl -EnvironmentName "NGROK"
    }
    'production' {
        if ([string]::IsNullOrEmpty($ProductionUrl)) {
            Write-ColorOutput "`n❌ Error: Production URL required. Use -ProductionUrl parameter." $colors.Error
            Write-ColorOutput "Example: .\test-clean-urls.ps1 -Environment production -ProductionUrl 'https://yourdomain.com'" $colors.Info
            exit 1
        }
        $allResults.Production = Test-Environment -BaseUrl $ProductionUrl -EnvironmentName "PRODUCTION"
    }
    'all' {
        $allResults.Localhost = Test-Environment -BaseUrl $localBase -EnvironmentName "LOCALHOST"
        
        if (![string]::IsNullOrEmpty($NgrokUrl)) {
            $allResults.Ngrok = Test-Environment -BaseUrl $NgrokUrl -EnvironmentName "NGROK"
        }
        
        if (![string]::IsNullOrEmpty($ProductionUrl)) {
            $allResults.Production = Test-Environment -BaseUrl $ProductionUrl -EnvironmentName "PRODUCTION"
        }
    }
}

# Overall summary
if ($allResults.Count -gt 1) {
    Write-ColorOutput "`n$('=' * 70)" $colors.Info
    Write-ColorOutput "OVERALL SUMMARY" $colors.Info
    Write-ColorOutput "$('=' * 70)" $colors.Info
    
    foreach ($env in $allResults.Keys) {
        $result = $allResults[$env]
        Write-ColorOutput "`n$env Environment:" $colors.Info
        Write-ColorOutput "  Total: $($result.Total) | Passed: $($result.Passed) | Failed: $($result.Failed)" $(if ($result.Failed -eq 0) { $colors.Success } else { $colors.Warning })
        Write-ColorOutput "  Success Rate: $([math]::Round($result.SuccessRate, 2))%" $(if ($result.Failed -eq 0) { $colors.Success } else { $colors.Warning })
    }
}

Write-ColorOutput "`n✅ Testing Complete!" $colors.Success
Write-ColorOutput @"

Next Steps:
1. If localhost tests pass: Start ngrok and test with -Environment ngrok -NgrokUrl <url>
2. If ngrok tests pass: Deploy to production and test with -Environment production -ProductionUrl <url>
3. Check browser DevTools Network tab for detailed request/response headers
4. Review Apache error logs if any tests fail: C:\laragon\www\logs\apache_error.log

"@ $colors.Info
