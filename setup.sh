#!/bin/bash
set -e

cd "$(dirname "$0")"
PROJECT_DIR="$(pwd)"

echo "=== Food Rescue Platform Setup ==="

# Find PHP
if ! command -v php &>/dev/null; then
    echo ""
    echo "ERROR: PHP is not installed."
    echo ""
    echo "Install one of these on your Mac, then re-run this script:"
    echo "  1. Laravel Herd (recommended): https://herd.laravel.com"
    echo "  2. Homebrew: brew install php composer"
    echo ""
    exit 1
fi

if ! command -v composer &>/dev/null; then
    echo ""
    echo "ERROR: Composer is not installed."
    echo "Install from: https://getcomposer.org or use Laravel Herd"
    exit 1
fi

echo "PHP:  $(php -v | head -1)"
echo "Composer: $(composer -V 2>/dev/null | head -1)"

# Install dependencies
if [ ! -d vendor ]; then
    echo ""
    echo "Installing Composer dependencies..."
    composer install --no-interaction
fi

# Environment
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate key if needed
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# Storage link
php artisan storage:link 2>/dev/null || true

# Migrate and seed
echo ""
echo "Running migrations and seeding demo data..."
php artisan migrate:fresh --seed --force

echo ""
echo "=== Setup complete! ==="
echo ""
echo "Start the server with:"
echo "  php artisan serve"
echo ""
echo "Then open: http://127.0.0.1:8000"
echo ""
echo "Demo login: beneficiary@foodrescue.test / FoodBridgeDemo!2026"
echo ""
