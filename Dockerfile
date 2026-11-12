# Demo/production image for Phanlink API. Works on Render, Fly, Railway or any Docker host.
FROM php:8.3-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql pdo_mysql zip opcache \
 && rm -rf /var/lib/apt/lists/*

RUN { echo 'opcache.enable=1'; echo 'opcache.enable_cli=1'; echo 'opcache.memory_consumption=64'; echo 'opcache.validate_timestamps=0'; } > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist \
 && composer require league/flysystem-aws-s3-v3:^3.0 --no-scripts --no-interaction --update-no-dev \
 && composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && rm -f bootstrap/cache/*.php

ENV PORT=8000
EXPOSE 8000

# Migrate & Seed on boot, then cache config/routes and serve.
ENV PHP_CLI_SERVER_WORKERS=3

CMD php artisan config:clear && \
    php artisan storage:link --force && \
    php artisan migrate --force -v && \
    if [ "$RUN_SEEDER" = "true" ]; then php artisan db:seed --force -v; fi && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT}
