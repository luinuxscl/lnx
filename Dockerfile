# syntax=docker/dockerfile:1

# La base la publica este kit: `ghcr.io/luinuxscl/lnx-php:8.5` es un paquete
# **publico** construido desde `docker/base/Dockerfile` por el workflow
# `base-image.yml`. Trae PHP 8.5 + Nginx con `intl`, `bcmath` y `gd` ya compiladas
# (`pdo_pgsql` viene de serversideup), asi que ninguna app recompila extensiones
# en su build.
#
# Reconstruirla solo al subir la version de PHP o cambiar la lista de extensiones.

############################################
# Base: PHP 8.5 + Nginx + extensiones (imagen del kit)
############################################
FROM ghcr.io/luinuxscl/lnx-php:8.5 AS base

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