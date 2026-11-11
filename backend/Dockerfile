FROM php:8.3-fpm-alpine

# PHP extensions required by Laravel.
RUN apk add --no-cache \
        icu-dev libzip-dev zip libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql intl zip gd bcmath opcache

# System tools used by the entrypoint.
RUN apk add --no-cache mysql-client

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Install dependencies first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# Copy the application.
COPY . .

RUN composer dump-autoload --optimize \
    && chmod +x docker-entrypoint.sh \
    && php artisan config:cache || true

EXPOSE 9000

ENTRYPOINT ["./docker-entrypoint.sh"]
CMD ["php-fpm"]
