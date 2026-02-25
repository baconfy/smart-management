############################################
# Composer: Install PHP dependencies
############################################
FROM serversideup/php:8.5-cli AS composer-build

USER root
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-req=ext-gettext

COPY . .

RUN mkdir -p storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

RUN composer dump-autoload --optimize --no-dev

############################################
# Node: Build frontend assets
############################################
FROM node:22-alpine AS node-build

WORKDIR /app

RUN apk add --no-cache php83 php83-tokenizer php83-mbstring php83-ctype php83-phar \
    && ln -sf /usr/bin/php83 /usr/bin/php

COPY package.json package-lock.json ./
RUN npm ci --no-audit

COPY vite.config.ts tsconfig.json tailwind.config.* ./
COPY resources ./resources
COPY app ./app
COPY routes ./routes
COPY artisan composer.json ./
COPY bootstrap ./bootstrap
COPY config ./config
COPY --from=composer-build /var/www/html/vendor ./vendor

RUN npm run build

############################################
# Production: Final image
############################################
FROM serversideup/php:8.5-fpm-nginx AS production

LABEL maintainer="Docker Starter Kit"

ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_STORAGE_LINK=true
ENV AUTORUN_LARAVEL_MIGRATION=true
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_MEMORY_LIMIT=256M
ENV SSL_MODE=off

WORKDIR /var/www/html

COPY --chown=www-data:www-data --from=composer-build /var/www/html /var/www/html
COPY --chown=www-data:www-data --from=node-build /app/public/build /var/www/html/public/build

RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
