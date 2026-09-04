@echo off
cd /d "%~dp0"

netstat -ano | findstr ":3306" >nul 2>nul
if errorlevel 1 (
    if exist "C:\xampp\mysql_start.bat" (
        echo [INFO] Starting XAMPP MySQL service...
        start "" "C:\xampp\mysql_start.bat"
        timeout /t 3 /nobreak >nul
    ) else (
        echo [WARNING] Port 3306 not detected. Please ensure MySQL is running.
    )
)

echo Starting FoodBridge at http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000
