#!/bin/bash
set -e

# Run composer install if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache config for better performance
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache
exec "$@"