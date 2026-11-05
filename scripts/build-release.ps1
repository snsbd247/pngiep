<#
  Builds an upload-ready release for cPanel / shared hosting (no Node.js on the server).

    powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1

  Output: release\riths-<date>.zip containing the Laravel app with
  - the compiled React site in public\spa
  - production-only Composer packages (vendor)
  - no .env, tests, logs, uploads or backups
  See docs\DEPLOYMENT.md for the upload steps.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$stage = Join-Path $root 'release\app'
$zip = Join-Path $root ("release\riths-" + (Get-Date -Format 'yyyy-MM-dd_HHmm') + '.zip')

Write-Host '1/4 Building the website (frontend)...' -ForegroundColor Cyan
Push-Location (Join-Path $root 'frontend')
# Fresh checkouts install exact versions; an existing install is reused (Windows may lock native binaries).
if (-not (Test-Path 'node_modules')) { npm ci --no-audit --no-fund; if ($LASTEXITCODE) { throw 'npm ci failed' } }
npm run build; if ($LASTEXITCODE) { throw 'npm run build failed' }
Pop-Location

Write-Host '2/4 Copying the application...' -ForegroundColor Cyan
if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force $stage | Out-Null
# /XD excluded folders, /XF excluded files. Keep empty storage folders (they hold .gitignore files).
robocopy (Join-Path $root 'backend') $stage /E /NFL /NDL /NJH /NJS /NP `
  /XD vendor node_modules tests .git .idea .vscode `
      "$root\backend\storage\logs" "$root\backend\storage\app\backups" "$root\backend\storage\pail" `
      "$root\backend\storage\framework\cache\data" "$root\backend\storage\framework\sessions" "$root\backend\storage\framework\views" `
  /XF .env .env.backup .env.production .phpunit.result.cache phpunit.xml *.log demo-content.json `
  | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed ($LASTEXITCODE)" }
# Uploaded media / private documents never ship with a release.
Get-ChildItem (Join-Path $stage 'storage\app\public') -Exclude '.gitignore', '.htaccess' -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force
Get-ChildItem (Join-Path $stage 'storage\app\private') -Exclude '.gitignore' -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force
Remove-Item (Join-Path $stage 'bootstrap\cache\*.php') -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $stage 'public\storage') -Force -Recurse -ErrorAction SilentlyContinue
foreach ($d in 'storage\logs', 'storage\app\backups', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views') {
  New-Item -ItemType Directory -Force (Join-Path $stage $d) | Out-Null
}

Write-Host '3/4 Installing production PHP packages...' -ForegroundColor Cyan
Push-Location $stage
composer install --no-dev --optimize-autoloader --no-interaction --no-progress; if ($LASTEXITCODE) { throw 'composer install failed' }
Pop-Location

Write-Host '4/4 Creating zip...' -ForegroundColor Cyan
if (Test-Path $zip) { Remove-Item $zip -Force }
# Windows' bsdtar writes "/" path separators (Compress-Archive on PowerShell 5.1 writes "\", which Linux unzip mangles).
& "$env:SystemRoot\System32\tar.exe" -a -c -f $zip -C $stage .
if ($LASTEXITCODE) { throw 'zip failed' }
Remove-Item $stage -Recurse -Force

Write-Host "Done: $zip" -ForegroundColor Green
Write-Host 'Next: follow docs\DEPLOYMENT.md (upload, .env, migrate, cron).'
