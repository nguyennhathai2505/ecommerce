FROM node:22-alpine AS assets

WORKDIR /app

COPY laravel/package.json laravel/package-lock.json ./
RUN npm ci

COPY laravel/resources ./resources
COPY laravel/public ./public
COPY laravel/vite.config.js ./

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY laravel/ ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.3-fpm-bookworm AS laravel-runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        xml \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY laravel/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && printf '%s\n' \
        'expose_php=Off' \
        'memory_limit=256M' \
        'upload_max_filesize=20M' \
        'post_max_size=22M' \
        'opcache.enable=1' \
        'opcache.validate_timestamps=0' \
        'opcache.memory_consumption=128' \
        'opcache.max_accelerated_files=20000' \
        > /usr/local/etc/php/conf.d/zz-production.ini

FROM nginx:1.28-alpine AS laravel-web

COPY deploy/nginx/laravel.conf /etc/nginx/conf.d/default.conf
COPY --from=laravel-runtime /var/www/html/public /var/www/html/public
