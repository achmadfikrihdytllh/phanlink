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
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

# Copy the app first, THEN add the S3 adapter: `dump-autoload --no-dev` only keeps packages that
# composer.json (the final copy) requires, so adding it before this COPY would drop it from the autoloader.
COPY . .
RUN composer require league/flysystem-aws-s3-v3:^3.0 --no-scripts --no-interaction --update-no-dev \
 && composer install --no-dev --no-scripts --no-interaction --prefer-dist \
 && composer dump-autoload --optimize --no-dev \
 && php -r 'require "vendor/autoload.php"; if (!class_exists("League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter")) { fwrite(STDERR, "S3 adapter missing from autoload\n"); exit(1); }' \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

ENV PORT=8000
EXPOSE 8000

# Migrate on boot (safe: only adds missing tables), then serve.
ENV PHP_CLI_SERVER_WORKERS=3

CMD echo "[boot] UPLOADS_DISK=${UPLOADS_DISK:-UNSET} DB_CONNECTION=${DB_CONNECTION:-UNSET} DB_HOST=${DB_HOST:-UNSET} AWS_BUCKET=${AWS_BUCKET:-UNSET} AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION:-UNSET} AWS_ENDPOINT=${AWS_ENDPOINT:-UNSET} AWS_URL=${AWS_URL:-UNSET} AWS_PATH_STYLE=${AWS_USE_PATH_STYLE_ENDPOINT:-UNSET} AWS_KEY_LEN=${#AWS_ACCESS_KEY_ID} AWS_SECRET_LEN=${#AWS_SECRET_ACCESS_KEY}"; \
    php artisan storage:link --force; \
    php artisan migrate --force && \
    { [ "$RUN_SEEDER" != "true" ] || php artisan db:seed --force; } && \
    php artisan config:cache && php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT}
