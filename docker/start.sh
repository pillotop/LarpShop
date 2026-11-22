#!/bin/sh
set -eu

# Runtime preparation also works when public storage is on a mounted disk.
php artisan config:clear --no-interaction
php artisan assets:prepare --no-interaction

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
