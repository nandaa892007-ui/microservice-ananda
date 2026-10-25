FROM php:8.4-fpm-alpine

# Install sistem dependencies dan extension MySQL
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    linux-headers \
    zip \
    unzip \
    git \
    curl \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql zip bcmath

# Copy Composer dari image resmi composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy source code aplikasi
COPY . .

# Install dependency PHP via Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Atur permission folder storage dan bootstrap/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 3003

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=3003 --no-reload"]
