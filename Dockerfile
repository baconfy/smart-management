############################################
# Build: PHP dependencies + Frontend assets
############################################
FROM serversideup/php:8.5-cli AS build

USER root
WORKDIR /var/www/html

# Install Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# --- Composer ---
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

# --- Vite build args ---
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME

# --- NPM ---
RUN npm ci --no-audit
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

# Copy application code from build stage
COPY --chown=www-data:www-data --from=build /var/www/html /var/www/html

# Ensure storage and cache directories exist with correct permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
