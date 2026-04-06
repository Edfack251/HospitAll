# HospitAll - Sync project to XAMPP htdocs
# Run from project root: powershell -ExecutionPolicy Bypass -File scripts\sync-to-xampp.ps1

$source = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$target = "C:\xampp\htdocs\HospitAll V1"

if (!(Test-Path "C:\xampp\htdocs")) {
    Write-Host "XAMPP htdocs not found. Create C:\xampp\htdocs or adjust path." -ForegroundColor Red
    exit 1
}

$dirs = @("app", "views", "public", "db", "vendor")
$files = @("composer.json", "composer.lock", ".env.example", "phpunit.xml", "index.php")

Write-Host "Syncing HospitAll to $target" -ForegroundColor Cyan
if (!(Test-Path $target)) { New-Item -ItemType Directory -Path $target -Force | Out-Null }

foreach ($d in $dirs) {
    $src = Join-Path $source $d
    if (Test-Path $src) {
        $dest = Join-Path $target $d
        Write-Host "  Copying $d/ ..." -ForegroundColor Gray
        robocopy $src $dest /MIR /NFL /NDL /NJH /NJS /NC /NS | Out-Null
        if ($LASTEXITCODE -ge 8) { Write-Host "  Warning: robocopy exit $LASTEXITCODE for $d" -ForegroundColor Yellow }
    }
}
foreach ($f in $files) {
    $src = Join-Path $source $f
    if (Test-Path $src) {
        Copy-Item $src -Destination (Join-Path $target $f) -Force
        Write-Host "  Copied $f" -ForegroundColor Gray
    }
}
# Copy .env if exists (user config)
if (Test-Path (Join-Path $source ".env")) {
    Copy-Item (Join-Path $source ".env") -Destination (Join-Path $target ".env") -Force
    Write-Host "  Copied .env" -ForegroundColor Gray
}
Write-Host "Done." -ForegroundColor Green
