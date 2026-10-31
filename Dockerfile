FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install \
        zip \
        pdo_mysql \
        pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

# Create an empty .env placeholder so Laravel's phpdotenv safeLoad() does not
# emit "Failed to open stream" PHP warnings during PHPUnit test runs.
# No secrets are stored here — Docker Compose injects real values at runtime
# via the `env_file` and `environment` keys in compose.yaml.
RUN touch .env

RUN composer dump-autoload \
    --optimize \
    --no-interaction \
    --no-scripts

EXPOSE 8000

# --no-reload is required: because an (empty) .env file exists in the image,
# `artisan serve` would otherwise strip the environment variables injected by
# Docker Compose (APP_KEY, DB_*) from the server process, causing HTTP 500.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
