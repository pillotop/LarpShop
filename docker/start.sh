#!/bin/sh
set -eu

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Runtime preparation also works when public storage is on a mounted disk.
php artisan config:clear --no-interaction
php artisan view:clear --no-interaction
php artisan assets:prepare --no-interaction

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
