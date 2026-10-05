FROM dunglas/frankenphp:1-php8.4

# PHP extensions the app needs in production (pdo_pgsql is the Postgres driver)
RUN install-php-extensions pdo_pgsql bcmath intl opcache pcntl zip \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Install dependencies first so this layer is cached until composer.lock changes
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
    --prefer-dist --no-interaction --no-progress

COPY . .

# Laravel needs these directories to exist and be writable
RUN mkdir -p storage/app/public \
             storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
 && composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

# Plain HTTP on 8080; the reverse proxy on the VPS terminates TLS
ENV SERVER_NAME=:8080
EXPOSE 8080

USER www-data
