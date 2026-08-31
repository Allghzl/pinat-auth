# =========================
# Frontend build
# =========================
FROM node:24-alpine AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ARG VITE_APP_NAME=PinatAuth
ENV VITE_APP_NAME=$VITE_APP_NAME

RUN npm run build


# =========================
# PHP dependencies
# =========================
FROM composer:2 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# =========================
# Production
# =========================
FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    curl \
    libpng \
    libzip \
    postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libzip-dev \
    postgresql-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    gd \
    bcmath \
    opcache \
    && apk del .build-deps

WORKDIR /var/www/html

COPY . .

COPY --from=composer-build /app/vendor ./vendor
COPY --from=frontend-build /app/public/build ./public/build

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/jwt \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]