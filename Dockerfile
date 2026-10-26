FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY . .
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpq-dev libzip-dev \
    && docker-php-ext-install bcmath exif mbstring opcache pdo_mysql pdo_pgsql pdo_sqlite zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-ports.conf /etc/apache2/ports.conf
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/unihealth.ini /usr/local/etc/php/conf.d/unihealth.ini

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app/ ./
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/unihealth-entrypoint

RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/unihealth-entrypoint"]
CMD ["apache2-foreground"]
