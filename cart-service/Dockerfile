FROM php:8.4-fpm-alpine

# Install sistem dependencies, linux-headers, dan extension PostgreSQL
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    linux-headers \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath

# Copy Composer dari image resmi composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy source code aplikasi
COPY . .

# Install dependency PHP via Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Atur permission folder storage dan bootstrap/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 3001

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=3001 --no-reload"]