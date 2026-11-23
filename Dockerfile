# Prod-подібний образ для перевірки: docker compose -f compose.run.yaml up --build

FROM dunglas/frankenphp:1-php8.5 AS php-base
RUN install-php-extensions pdo_pgsql opcache
WORKDIR /app

# Залежності Composer без dev + згенеровані Wayfinder-файли (потрібен PHP).
FROM php-base AS vendor
RUN apt-get update && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan wayfinder:generate --with-form

# Збірка фронтенду. Плагін Wayfinder викликає `php artisan`, але файли вже
# згенеровано на попередньому етапі, тому php тут заглушка.
FROM node:22 AS assets
RUN printf '#!/bin/sh\nexit 0\n' > /usr/local/bin/php && chmod +x /usr/local/bin/php
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY --from=vendor /app/resources ./resources
COPY vite.config.ts tsconfig.json components.json ./
COPY public ./public
RUN npm run build

# Фінальний образ: без Node, без dev-залежностей.
FROM php-base AS final
ENV SERVER_NAME=:8080
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
EXPOSE 8080
ENTRYPOINT ["app-entrypoint"]
CMD ["--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
