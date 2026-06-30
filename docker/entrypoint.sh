#!/bin/sh
# Container startup script.
# Runs before php-fpm, queue:work, or schedule:work.
set -e

# ------------------------------------------------------------------
# 1. Fix ownership of volumes that Docker may have created as root
# ------------------------------------------------------------------
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ------------------------------------------------------------------
# 2. Sync built public assets into the shared public_assets volume.
#    This ensures Nginx always serves the current image's built files
#    after a redeploy, even though the volume persists between builds.
#    Runs only for the web (php-fpm) process — workers skip this.
# ------------------------------------------------------------------
if [ "${1:-}" = "php-fpm" ]; then
    rsync -a --delete --exclude=/storage /var/www/app-public/ /var/www/html/public/

    # Create public/storage symlink (storage:link) if it does not exist yet.
    # Idempotent — safe to run on every startup.
    if [ ! -L /var/www/html/public/storage ]; then
        php artisan storage:link --no-interaction 2>/dev/null || true
    fi
fi

# ------------------------------------------------------------------
# 3. Hand off to the requested process (php-fpm, php artisan …, etc.)
# ------------------------------------------------------------------
exec "$@"
