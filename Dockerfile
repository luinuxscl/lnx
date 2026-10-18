# syntax=docker/dockerfile:1

# Hay una imagen base propia (ghcr.io/luinuxscl/lnx-php:8.5, con las extensiones
# ya compiladas) publicada por .github/workflows/base-image.yml desde
# docker/base/Dockerfile. Mientras ese paquete siga **privado** no se puede usar
# en el FROM: un runner de GitHub no puede leer paquetes privados de otro repo.
# Si se hace publico, basta cambiar el FROM de la etapa base por
# `FROM ghcr.io/luinuxscl/lnx-php:8.5 AS base` y borrar el install-php-extensions.

############################################
# Base: PHP 8.5 + Nginx (serversideup)
############################################
FROM serversideup/php:8.5-fpm-nginx AS base

USER root

RUN install-php-extensions pdo_pgsql intl bcmath gd

USER www-data

############################################
# Vendor: dependencias de Composer (sin dev)
############################################
FROM base AS vendor

# Token de GitHub para que Composer baje los paquetes privados (lnx-ui y, en una
# app, tambien el modulo). Lo pasa el build de GitHub Actions como build arg
# (COMPOSER_AUTH). Si la etapa que corre Composer no declara este ARG, el valor se
# ignora en silencio y el build falla con 404 al resolver los paquetes privados.
ARG COMPOSER_AUTH

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