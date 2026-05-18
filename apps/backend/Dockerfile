FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist; fi

EXPOSE 8000
CMD ["sh", "-c", "[ -f .env ] || cp .env.docker .env && rm -f public/storage && php artisan storage:link && php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
