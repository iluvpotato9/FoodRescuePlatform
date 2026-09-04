# FoodBridge Startup Script for PowerShell
$ErrorActionPreference = "Stop"

Set-Location -Path $PSScriptRoot

# Check MySQL on port 3306
$mysqlRunning = $false
try {
    $tcp = Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue
    $mysqlRunning = $tcp.TcpTestSucceeded
} catch {
    $mysqlRunning = $false
}

if (-not $mysqlRunning) {
    if (Test-Path "C:\xampp\mysql_start.bat") {
        Write-Host "[INFO] MySQL not running on port 3306. Starting XAMPP MySQL service..." -ForegroundColor Cyan
        Start-Process "cmd.exe" -ArgumentList "/c C:\xampp\mysql_start.bat" -WindowStyle Minimized
        Start-Sleep -Seconds 3
    } else {
        Write-Host "[WARNING] MySQL port 3306 is not detected. Please make sure MySQL is running in XAMPP." -ForegroundColor Yellow
    }
}

Write-Host "Starting FoodBridge at http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop the server." -ForegroundColor Gray
php artisan serve --host=127.0.0.1 --port=8000
