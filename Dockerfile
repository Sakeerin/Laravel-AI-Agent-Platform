# syntax=docker/dockerfile:1
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY public ./public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.2-fpm-alpine
WORKDIR /var/www/html
RUN apk add --no-cache nginx supervisor icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl pdo_mysql opcache \
    && rm -rf /var/cache/apk/*
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY deploy/nginx.conf /etc/nginx/http.d/default.conf
COPY deploy/supervisord.conf /etc/supervisord.conf

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
