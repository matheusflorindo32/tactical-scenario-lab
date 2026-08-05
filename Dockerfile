FROM php:8.4-cli
RUN apt-get update && apt-get install -y git unzip libsqlite3-dev && docker-php-ext-install pdo_sqlite && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader && mkdir -p database storage/framework/{cache,sessions,views} storage/logs && touch database/database.sqlite
EXPOSE 8080
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
