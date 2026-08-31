# =========================
# Build stage
# PHP + Composer + Node
# =========================
FROM php:8.4-cli-alpine AS builder

RUN apk add --no-cache \
    git \
    curl \
    nodejs \
    npm \
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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# =========================
# Install PHP dependencies
# =========================
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# =========================
# Install Node dependencies
# =========================
COPY package.json package-lock.json ./

RUN npm ci


# =========================
# Copy application
# =========================
COPY . .


# =========================
# Finish Laravel setup
# =========================
RUN composer dump-autoload --optimize


# =========================
# Build frontend
# =========================
ARG VITE_APP_NAME=PinatAuth
ENV VITE_APP_NAME=$VITE_APP_NAME

RUN npm run build


# =========================
# Production image
# =========================
FROM php:8.4-cli-alpine AS production

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
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=builder /var/www/html /var/www/html

# Node modules tidak dibutuhkan runtime
RUN rm -rf node_modules \
    && mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/keys \
    bootstrap/cache \
    && chown -R www-data:www-data \
    storage \
    bootstrap/cache

ENV APP_ENV=production

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]