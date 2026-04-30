#!/bin/bash
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

composer dump-autoload --optimize --quiet

cat > .env << EOF
APP_NAME="CRS Laravel"
APP_ENV=${APP_ENV:-local}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost}
APP_KEY=${APP_KEY:-}

LOG_CHANNEL=stderr

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-crs_laravel}
DB_USERNAME=${DB_USERNAME:-crs_user}
DB_PASSWORD=${DB_PASSWORD:-crs_pass}

SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost}
EOF

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:clear

until php artisan migrate --force; do
    echo "Waiting for database..."
    sleep 3
done

exec apache2-foreground
