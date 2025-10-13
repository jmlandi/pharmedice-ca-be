#!/bin/bash

echo "Starting Laravel application..."

# Create required directories
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx
mkdir -p /var/run

# Wait for database to be ready (optional, adjust if needed)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    until nc -z -v -w30 $DB_HOST ${DB_PORT:-5432}
    do
        echo "Waiting for database connection..."
        sleep 2
    done
    echo "Database is ready!"
fi

# Run Laravel setup commands
echo "Running Laravel setup..."

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Run migrations (optional - uncomment if you want auto-migrations)
# php artisan migrate --force

# Start supervisord
echo "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
