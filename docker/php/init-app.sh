#!/bin/sh
set -eu

php artisan migrate --force

if [ ! -L public/storage ]; then
    php artisan storage:link
fi
