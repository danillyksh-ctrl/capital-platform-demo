FROM node:24-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip git && docker-php-ext-install pdo_pgsql zip && pecl install redis && docker-php-ext-enable redis && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader && touch .env && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /app/public/build ./public/build
USER www-data
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
