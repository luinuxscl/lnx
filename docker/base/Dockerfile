# syntax=docker/dockerfile:1

############################################
# Imagen base compartida por las apps lnx
############################################
# PHP 8.5 + Nginx + las extensiones que el kit necesita, ya compiladas.
#
# Sin esto, cada app recompila intl/bcmath/gd en cada build (~4 min medidos en
# Coolify). Las apps hacen `FROM ghcr.io/luinuxscl/lnx-php:8.5` y el paso caro
# desaparece del build de la app.
#
# Reconstruir solo al subir la version de PHP o cambiar la lista de extensiones.
# El paquete se publica **publico** a proposito: no contiene codigo de ninguna
# app, solo el runtime.
#
# pdo_pgsql ya viene en la imagen de serversideup; estas se compilan.

FROM serversideup/php:8.5-fpm-nginx

USER root

RUN install-php-extensions intl bcmath gd

USER www-data