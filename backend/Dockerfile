FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libssl-dev libzip-dev \
    && pecl install mongodb-1.21.0 \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --optimize \
    && chmod +x docker-entrypoint.sh bin/console \
    && mkdir -p var config/jwt

EXPOSE 8000

ENTRYPOINT ["./docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
