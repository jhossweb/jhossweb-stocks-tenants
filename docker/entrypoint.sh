#!/bin/sh

set -eu

cd /app

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY no está definido. Configúralo como variable de entorno en Railway." >&2
    exit 1
fi

PORT="${PORT:-8080}"

echo "Esperando a que la base de datos esté disponible..."
attempt=0
until php artisan db:show >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "ERROR: la base de datos no respondió después de 60 segundos." >&2
        exit 1
    fi
    sleep 2
done

echo "Ejecutando migraciones centrales..."
php artisan migrate --force --no-interaction

echo "Ejecutando migraciones de tenant..."
php artisan tenants:migrate --force --no-interaction

echo "Sincronizando permisos de tenant..."
php artisan tenants:sync-permissions --no-interaction

echo "Asegurando subdominios de tenant..."
php artisan tenancy:backfill-domains --no-interaction

echo "Enlazando storage público..."
php artisan storage:link --force

echo "Generando cachés de configuración, rutas y vistas..."
# Limpieza explícita de cachés de archivo: se evita `optimize:clear` para no
# depender de un store de caché concreto (p. ej. la tabla `cache`).
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Configurando nginx en el puerto ${PORT}..."
sed "s/__PORT__/${PORT}/g" \
    /app/docker/nginx/default.conf.template \
    > /etc/nginx/http.d/default.conf

php-fpm -D

exec nginx -g 'daemon off;'
