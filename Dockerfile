# ---------------------------------------------------
# Etapa 1: Dependencias PHP (Composer)
# ---------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
# Se añade --ignore-platform-reqs para que no falle por falta de ext-intl en la imagen de composer
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------------------------------------------------
# Etapa 2: Compilación de Assets (Node + PNPM)
# ---------------------------------------------------
FROM node:22-alpine AS build
WORKDIR /app

RUN corepack enable
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml* ./
# En la etapa de build sí necesitamos el entorno de composer si Vite importa archivos desde /vendor (ej. Ziggy o Tighten)
COPY --from=vendor /app/vendor /app/vendor
RUN pnpm install --frozen-lockfile

COPY . .
RUN pnpm build

# ---------------------------------------------------
# Etapa 3: Imagen Final de Producción (PHP-FPM + Nginx)
# ---------------------------------------------------
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    libpq \
    icu-libs \
    libzip \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache pcntl zip \
    && apk del .build-deps

RUN { \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > $PHP_INI_DIR/conf.d/99-opcache.ini

WORKDIR /app

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=build /app/public/build /app/public/build
# Descomenta la siguiente línea solo si tu pnpm build genera SSR y tienes Node instalado:
# COPY --from=build /app/bootstrap/ssr /app/bootstrap/ssr

COPY docker/nginx/nginx.conf /etc/nginx/http.d/default.conf

RUN mkdir -p /app/storage/framework/cache/data \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs \
             /app/bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && php artisan package:discover --ansi

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]