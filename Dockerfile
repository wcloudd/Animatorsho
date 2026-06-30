# =============================================================
# Stage 1: build — PHP-CLI + Composer + Node
#   Installs all dependencies, generates Wayfinder types,
#   and builds the Vite/React frontend assets.
# =============================================================
FROM php:8.3-cli-alpine AS build

RUN apk add --no-cache \
        bash git curl unzip \
        nodejs npm \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev \
        postgresql-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql bcmath intl zip gd mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies without running scripts or building autoloader yet
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copy full application source (respects .dockerignore)
COPY . .

# Build the optimized classmap autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Generate Wayfinder TypeScript types (resources/js/{actions,routes,wayfinder}/ are
# gitignored and must be produced here before npm run build).
# A minimal .env with a temporary APP_KEY lets artisan bootstrap without real credentials.
RUN php -r 'echo "APP_KEY=base64:" . base64_encode(random_bytes(32)) . "\nAPP_ENV=local\n";' > .env \
 && php artisan wayfinder:generate --no-interaction \
 && rm -f .env

# Install Node dependencies (lockfile-exact) and build frontend
RUN npm ci
RUN npm run build

# =============================================================
# Stage 2: app — PHP-FPM production runtime
# =============================================================
FROM php:8.3-fpm-alpine AS app

LABEL org.opencontainers.image.title="animatorsho" \
      org.opencontainers.image.source="https://github.com/your-org/animatorsho"

# Runtime libraries only (no -dev headers)
RUN apk add --no-cache \
        libpng libjpeg-turbo freetype \
        libzip icu \
        libpq \
        rsync

# PHP extensions: install with build deps, then remove build deps
RUN apk add --no-cache --virtual .build-deps \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev postgresql-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql \
        bcmath intl zip gd \
        pcntl opcache exif \
 && apk del .build-deps

WORKDIR /var/www/html

# Copy built application (vendor, public/build, all source) from build stage
COPY --from=build --chown=www-data:www-data /var/www/html .

# Remove any secrets that may have been placed in the build context
RUN rm -f .env .env.backup .env.production

# Keep a pristine copy of the built public/ directory.
# The entrypoint syncs this into the public_assets volume on every startup,
# so Nginx always serves assets from the current image after a redeploy.
RUN mkdir -p /var/www/app-public && cp -a public/. /var/www/app-public/

# Pre-create writable runtime directories with correct ownership
RUN install -d -o www-data -g www-data -m 775 \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

# PHP runtime and opcache configuration
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

# Container startup script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
