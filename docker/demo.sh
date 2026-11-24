#!/bin/sh
# Run from any directory. Host PHP, Composer, Node, and real provider keys are unnecessary.
set -eu
cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
umask 077

environment_file=.env.docker-demo
command=${1:-setup}

compose() {
    docker compose --env-file "$environment_file" -p larpshop-demo -f compose.demo.yaml "$@"
}

if [ "$command" = setup ]; then
    # An explicit empty env file prevents Compose from reading the developer's .env.
    if [ ! -f "$environment_file" ]; then
        : > "$environment_file"
    fi
    compose build app
    if [ ! -s "$environment_file" ]; then
        compose run --rm --no-deps --entrypoint php app -r '
            echo "DEMO_APP_KEY=base64:".base64_encode(random_bytes(32)).PHP_EOL;
            echo "DEMO_DB_PASSWORD=".bin2hex(random_bytes(24)).PHP_EOL;
            echo "DEMO_DB_ROOT_PASSWORD=".bin2hex(random_bytes(24)).PHP_EOL;
        ' > "$environment_file.tmp"
        mv "$environment_file.tmp" "$environment_file"
    fi
    compose up -d --wait db mailpit
    compose run --rm --no-deps app php artisan migrate --force --no-interaction
    compose run --rm --no-deps app php artisan assets:prepare --no-interaction
    compose run --rm --no-deps app php artisan db:seed --force --no-interaction
    compose up -d --wait
    compose exec -T app php docker/verify.php
    printf '\nDemo ready. Default URLs: http://localhost:8080 and mail http://localhost:8026\n'
    exit 0
fi

if [ ! -s "$environment_file" ]; then
    printf 'Run sh docker/demo.sh setup first.\n' >&2
    exit 1
fi

case "$command" in
    start) compose up -d --wait ;;
    stop) compose stop ;;
    logs) compose logs --tail=80 ;;
    verify) compose exec -T app php docker/verify.php ;;
    test)
        # The fixed project and database names isolate tests from all other stacks.
        compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "CREATE DATABASE IF NOT EXISTS larpshop_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON larpshop_testing.* TO '\''larpshop'\''@'\''%'\'';"'
        compose exec -T -e APP_ENV=testing app php artisan test
        ;;
    *) printf 'Usage: sh docker/demo.sh {setup|start|stop|verify|test|logs}\n' >&2; exit 1 ;;
esac
