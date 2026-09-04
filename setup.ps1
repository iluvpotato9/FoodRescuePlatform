# FoodBridge Setup Script for PowerShell (MySQL)
$ErrorActionPreference = "Stop"

Set-Location -Path $PSScriptRoot

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "FoodBridge setup for Windows (MySQL)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "ERROR: PHP 8.2 or newer is not available in PATH. Please install PHP and enable pdo_mysql."
    exit 1
}

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Error "ERROR: Composer is not available in PATH. Please install Composer."
    exit 1
}

if (-not (Test-Path "vendor")) {
    Write-Host "Installing PHP dependencies..." -ForegroundColor Yellow
    composer install --no-interaction
}

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    php artisan key:generate --force
}

Write-Host "Running database migrations and seeders on MySQL..." -ForegroundColor Yellow
php artisan migrate:fresh --seed --force

if (Test-Path "public\storage" -PathType Leaf) {
    Remove-Item "public\storage" -Force
}
php artisan storage:link 2>$null

Write-Host ""
Write-Host "Setup complete!" -ForegroundColor Green
Write-Host "Run .\start.ps1 or .\start.bat, then open http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Demo account: beneficiary@foodrescue.test / FoodBridgeDemo!2026" -ForegroundColor Gray
