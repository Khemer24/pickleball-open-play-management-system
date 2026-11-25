FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
RUN mkdir -p public && npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm AS base

RUN install-php-extensions pdo_mysql zip

WORKDIR /app

FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && chmod -R ug+rwX storage bootstrap/cache

FROM base AS runtime

COPY --from=dependencies --chown=www-data:www-data /app /app
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile

RUN chown -R www-data:www-data /config/caddy /data/caddy

ENV APP_ENV=production \
    APP_NAME=RallyCourt \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

USER www-data

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
