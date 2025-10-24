$adminPath = "c:\laragon\www\Smartphone-Accessories\admin"
$files = @("categories.php", "brands.php", "users.php", "contacts.php", "settings.php", "special-access.php", "maintenance-manager.php", "index.php")

$updated = 0
$skipped = 0

foreach ($file in $files) {
    $filePath = Join-Path $adminPath $file
    
    if (Test-Path $filePath) {
        $content = Get-Content $filePath -Raw -Encoding UTF8
        
        # Check if already has dark mode files
        if ($content -match 'admin-dark-mode\.css') {
            Write-Host "⏭️  $file - already has dark mode" -ForegroundColor Yellow
            $skipped++
            continue
        }
        
        # Add dark mode CSS after admin.css
        $content = $content -replace '(<link rel="stylesheet" href="\.\./css/admin\.css">)', "`$1`n    <link rel=""stylesheet"" href=""../css/admin-dark-mode.css"">"
        
        # Add dark mode JS after font-awesome
        $content = $content -replace '(<link href="https://cdnjs\.cloudflare\.com/ajax/libs/font-awesome/[^"]+\.css" rel="stylesheet">)', "`$1`n    <script src=""../js/admin-dark-mode.js""></script>"
        
        # Save the file
        [System.IO.File]::WriteAllText($filePath, $content, [System.Text.UTF8Encoding]::new($false))
        
        Write-Host "✅ $file - updated successfully" -ForegroundColor Green
        $updated++
    } else {
        Write-Host "❌ $file - not found" -ForegroundColor Red
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "✅ Updated: $updated files" -ForegroundColor Green
Write-Host "⏭️  Skipped: $skipped files" -ForegroundColor Yellow
Write-Host "========================================`n" -ForegroundColor Cyan
