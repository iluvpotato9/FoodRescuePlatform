@echo off
setlocal
cd /d "%~dp0"

echo ========================================
echo FoodBridge setup for Windows (MySQL)
echo ========================================

where php >nul 2>nul
if errorlevel 1 (
    echo ERROR: PHP 8.2 or newer is not available in PATH.
    echo Install PHP and enable the pdo_mysql and openssl extensions.
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo ERROR: Composer is not available in PATH.
    echo Download Composer from https://getcomposer.org/download/
    exit /b 1
)

if not exist vendor (
    echo Installing PHP dependencies...
    call composer install --no-interaction
    if errorlevel 1 exit /b 1
)

if not exist .env copy .env.example .env >nul

findstr /B /C:"APP_KEY=base64:" .env >nul
if errorlevel 1 (
    php artisan key:generate --force
    if errorlevel 1 exit /b 1
)

echo Running database migrations and seeders on MySQL...
php artisan migrate:fresh --seed --force
if errorlevel 1 (
    echo.
    echo [NOTE] Migration failed. Please make sure MySQL service (e.g. XAMPP MySQL) is running.
    exit /b 1
)

if exist public\storage (
    if not exist public\storage\ (
        del /f /q public\storage
    )
)
php artisan storage:link >nul 2>nul

echo.
echo Setup complete.
echo Run start.bat, then open http://127.0.0.1:8000
echo Demo account: beneficiary@foodrescue.test / FoodBridgeDemo!2026
endlocal
