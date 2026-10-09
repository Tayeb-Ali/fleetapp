# Production image: PHP-FPM 8.3 + required extensions
FROM php:8.3-fpm-alpine AS base

# System deps for gd / zip / sqlite / mysql
RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        sqlite \
        sqlite-dev \
        mysql-client \
        git \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_sqlite \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        opcache \
        pcntl \
    && rm -rf /var/cache/apk/*

# Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader \
    && composer clear-cache

# Copy application
COPY . .

# Production optimizations + storage permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/private bootstrap/cache database \
    && touch database/database.sqlite || true \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan optimize:clear || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data bootstrap/cache storage

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
