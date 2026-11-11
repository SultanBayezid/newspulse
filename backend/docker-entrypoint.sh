#!/bin/sh
set -e

cd /var/www/backend

# Wait for the database to accept connections.
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for database at ${DB_HOST:-db}:${DB_PORT:-3306}…"
    until mysqladmin ping -h "${DB_HOST:-db}" -P "${DB_PORT:-3306}" \
            -u "${DB_USERNAME:-newspulse}" -p"${DB_PASSWORD:-newspulse_secret}" --silent 2>/dev/null; do
        sleep 2
    done
    echo "Database is ready."
fi

# Generate an application key on first boot when none is configured.
if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=.\+' .env 2>/dev/null; then
    echo "Generating APP_KEY…"
    php artisan key:generate --force || true
fi

# Prepare storage, then migrate (seeding once on a fresh database).
php artisan storage:link || true

php artisan migrate --force

if [ "$SEED_DEMO_DATA" = "1" ]; then
    php artisan db:seed --force || true
fi

php artisan config:cache
php artisan route:cache

exec "$@"
