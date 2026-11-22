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
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        gd \
        exif \
        zip \
        pdo_mysql \
        pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.revalidate_freq=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# A banner group can upload five images of up to 8 MB in one request.
RUN { \
        echo 'upload_max_filesize=8M'; \
        echo 'post_max_size=48M'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

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
CMD ["sh", "docker/start.sh"]
