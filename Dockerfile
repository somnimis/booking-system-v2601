# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies (clean up apt cache to reduce image size)
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
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy app files
COPY . .

# Copy SSL if exists
RUN mkdir -p /opt/render/project/src/ssl
COPY ssl/ca.pem /opt/render/project/src/ssl/ca.pem

# Optimized Apache configuration
RUN sed -i '/<\/VirtualHost>/i \
<Directory /var/www/html/public>\
    Options -Indexes +FollowSymLinks\
    AllowOverride None\
    Require all granted\
    RewriteEngine On\
    # Handle Authorization Header\
    RewriteCond %{HTTP:Authorization} .\
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\
    # Send Requests To Front Controller\
    RewriteCond %{REQUEST_FILENAME} !-d\
    RewriteCond %{REQUEST_FILENAME} !-f\
    RewriteRule ^ index.php [L]\
</Directory>' /etc/apache2/sites-available/000-default.conf

# Disable directory indexing for security
RUN sed -i 's/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/' /etc/apache2/apache2.conf

# Set permissions for Laravel
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Optimize PHP.ini for production
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose port 80
EXPOSE 80

# Optimize the startup command
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]