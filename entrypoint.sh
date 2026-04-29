#!/bin/bash
set -e

APP_ENV=${APP_ENV:-local}

echo "APP_ENV: $APP_ENV"
echo "Running application..."

# =========================
# PERMISSIONS
# =========================
echo "Adjusting permissions..."

mkdir -p \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache || true

chmod -R 775 storage bootstrap/cache || true

# =========================
# LOADING VARIABLES
# =========================
ENV_PATH="/var/www/html/.env"

if [ "$APP_ENV" = "local" ]; then
    echo "Local environment → loading .env"

    if [ -f "$ENV_PATH" ]; then
        echo ".env found"
    else
        echo ".env not found"
    fi
else
    echo "$APP_ENV environment → using Azure"

    if [ -f "/usr/local/bin/azureService.sh" ]; then 
        /usr/local/bin/azureService.sh 
    else 
        echo "azureService.sh not found" 
    fi
fi

# =========================
# VARIABLES (SAFE LOAD)
# =========================
if [ -f "$ENV_PATH" ]; then
    APP_VERSION=$(grep '^APP_VERSION=' "$ENV_PATH" | cut -d '=' -f2)
    NEW_RELIC_ENABLE=$(grep '^NEW_RELIC_ENABLE=' "$ENV_PATH" | cut -d '=' -f2)
    NEWRELICLICENSEKEY=$(grep '^NEWRELICLICENSEKEY=' "$ENV_PATH" | cut -d '=' -f2)
else
    echo "Skipping .env variable extraction"
fi

echo "APP_VERSION: ${APP_VERSION:-N/A}"
echo "NEW_RELIC_ENABLE: ${NEW_RELIC_ENABLE:-false}"

export NEW_RELIC_ENABLE NEWRELICLICENSEKEY

# =========================
# NEW RELIC
# =========================
if [ "$NEW_RELIC_ENABLE" = "true" ]; then
    echo "Setting up New Relic..."

    envsubst < /etc/php.d/newrelic.ini > /tmp/newrelic.ini
    mv /tmp/newrelic.ini /etc/php.d/newrelic.ini    

    pkill newrelic-daemon || true
    newrelic-daemon --logfile /var/log/newrelic/newrelic-daemon.log --port @newrelic
else
    echo "New Relic is disabled."
fi

# =========================
# LARAVEL
# =========================
echo "Cleaning Laravel cache..."
rm -f bootstrap/cache/*.php

if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing Laravel... (prod)"
    php artisan config:cache || true
    php artisan route:cache || true
else
    echo "Development mode (no cache)..."
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

# =========================
# RUN APP
# =========================

if [ "$APP_ENV" = "production" ]; then
    echo "Starting PHP-FPM (recommended for production)"
    exec php-fpm
else
    echo "Starting Laravel dev server"
    exec php -d max_execution_time=30 artisan serve --host=0.0.0.0 --port=8000
fi