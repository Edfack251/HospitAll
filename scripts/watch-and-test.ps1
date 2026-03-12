# HospitAll - File watcher for automatic PHPUnit execution
# Run: powershell -ExecutionPolicy Bypass -File scripts\watch-and-test.ps1

$projectRoot = Split-Path -Parent $PSScriptRoot
if ($PWD.Path -ne $projectRoot) { Set-Location $projectRoot }

$phpPath = "C:\xampp\php\php.exe"
$phpunitPath = "vendor\bin\phpunit"

$watchedPaths = @(
    "app",
    "tests",
    "config",
    "public\api"
)

$watchedExtensions = @(".php", ".env")

Write-Host "HospitAll - watching for changes (Ctrl+C to stop)" -ForegroundColor Cyan
Write-Host "Paths: $($watchedPaths -join ', ')" -ForegroundColor Gray
Write-Host ""

function Run-PHPUnit {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Running PHPUnit..." -ForegroundColor Yellow
    $output = & $phpPath $phpunitPath 2>&1
    $exitCode = $LASTEXITCODE
    Write-Host $output
    if ($exitCode -eq 0) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] OK" -ForegroundColor Green
    } else {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] FAIL (exit $exitCode)" -ForegroundColor Red
    }
    Write-Host ""
}

# Initial run
Run-PHPUnit

$fsw = @{}
foreach ($path in $watchedPaths) {
    $fullPath = Join-Path $projectRoot $path
    if (Test-Path $fullPath) {
        $fsw[$path] = New-Object System.IO.FileSystemWatcher
        $fsw[$path].Path = $fullPath
        $fsw[$path].IncludeSubdirectories = $true
        $fsw[$path].Filter = "*.*"
        $fsw[$path].NotifyFilter = [System.IO.NotifyFilters]::FileName -bor [System.IO.NotifyFilters]::LastWrite
    }
}

$triggerFile = Join-Path $env:TEMP "hospitall-run-tests.flag"

$onChange = {
    $path = $Event.SourceEventArgs.FullPath
    $ext = [System.IO.Path]::GetExtension($path)
    $name = $Event.SourceEventArgs.Name
    if (($ext -in @(".php", ".env") -or $ext -eq "") -and $name -notmatch "~\$") {
        $tfile = Join-Path $env:TEMP "hospitall-run-tests.flag"
        "1" | Out-File -FilePath $tfile -Force
    }
}

$handlers = @()
foreach ($key in $fsw.Keys) {
    $h1 = Register-ObjectEvent $fsw[$key] "Changed" -Action $onChange
    $h2 = Register-ObjectEvent $fsw[$key] "Created" -Action $onChange
    $handlers += $h1, $h2
}

$script:lastRun = [DateTime]::MinValue
$debounceMs = 500

try {
    while ($true) {
        Start-Sleep -Milliseconds 200
        if ((Test-Path $triggerFile) -and (([DateTime]::Now - $script:lastRun).TotalMilliseconds -gt $debounceMs)) {
            Remove-Item $triggerFile -Force -ErrorAction SilentlyContinue
            $script:lastRun = [DateTime]::Now
            Run-PHPUnit
        }
    }
} finally {
    foreach ($h in $handlers) { Unregister-Event -SourceIdentifier $h.Name -ErrorAction SilentlyContinue }
    foreach ($f in $fsw.Values) { $f.Dispose() }
}
