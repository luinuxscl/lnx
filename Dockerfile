# syntax=docker/dockerfile:1

############################################
# Base: imagen compartida de las apps lnx
############################################
# PHP 8.5 + Nginx con las extensiones (pdo_pgsql, intl, bcmath, gd) ya
# compiladas, para no recompilarlas en cada build. Se construye desde
# docker/base/Dockerfile y se publica con .github/workflows/base-image.yml.
FROM ghcr.io/luinuxscl/lnx-php:8.5 AS base

############################################
# Vendor: dependencias de Composer (sin dev)
############################################
FROM base AS vendor

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

############################################
# Assets: compilar JS/CSS con Vite
# (necesita vendor/ para los @import de Flux)
############################################
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./

RUN npm ci

COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

############################################
# App: imagen final de producción
############################################
FROM base AS app

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache