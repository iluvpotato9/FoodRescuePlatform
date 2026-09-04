#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
echo "Starting FoodBridge at http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000
