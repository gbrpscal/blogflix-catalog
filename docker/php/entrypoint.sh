#!/bin/sh
set -eu

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "${APP_ENV:-production}" != "testing" ]; then
    php artisan config:cache --quiet
    php artisan route:cache --quiet
fi

exec "$@"
