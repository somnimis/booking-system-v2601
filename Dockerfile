# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy app files
COPY . .

# Copy SSL if exists
# Make sure ssl/ca.pem is in your repo root
RUN mkdir -p /opt/render/project/src/ssl
COPY ssl/ca.pem /opt/render/project/src/ssl/ca.pem

# Set Apache to serve Laravel's public folder
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

# Set permissions for Laravel
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 8000
EXPOSE 80

# Check if vendor exists, if not run install (should only happen first time)
CMD ["bash", "-c", "if [ ! -d \"vendor\" ]; then composer install --no-dev --optimize-autoloader; fi && php artisan migrate --force && apache2-foreground"]