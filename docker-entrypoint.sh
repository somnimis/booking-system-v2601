#!/bin/bash
set -e

# 1. Dynamically replace the port in Apache config at runtime
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT..."
    # Replace port 80 in ports.conf
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    # Replace the port in VirtualHost
    sed -i "s/:80>/:$PORT>/g" /etc/apache2/sites-available/000-default.conf
fi

# 2. Run composer install if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

# 3. Run migrations
echo "Running migrations..."
php artisan migrate --force

# 4. Clear and cache config for better performance
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Start Apache
exec "$@"