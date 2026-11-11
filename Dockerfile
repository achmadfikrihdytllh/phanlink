# Demo/production image for Phanlink API. Works on Render, Fly, Railway or any Docker host.
FROM php:8.3-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist \
 && composer require league/flysystem-aws-s3-v3:^3.0 --no-scripts --no-interaction --update-no-dev \
 && composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

ENV PORT=8000
EXPOSE 8000

# Migrate on boot (safe: only adds missing tables), then serve.
CMD php artisan storage:link --force; \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT}
