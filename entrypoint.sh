#!/bin/bash
set -e

APP_ENV=${APP_ENV:-local}
echo "===== ENVIRONMENT ====="
echo -e "[INFO] APP_ENV: $APP_ENV \n"

echo "===== STARTUP ====="
echo "[INFO] Running application..."

# =========================
# PERMISSIONS
# =========================
echo -e "[INFO] Adjusting permissions... \n"

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
echo "===== CONFIGURATION ====="
ENV_PATH="/var/www/html/.env"

if [ "$APP_ENV" = "local" ]; then
    echo "[INFO] Local environment → loading .env"

    if [ -f "$ENV_PATH" ]; then
        echo -e "[SUCCESS] .env found \n"
    else
        echo -e "[ERROR] .env not found  \n"
    fi
else
    echo "[INFO] $APP_ENV environment → using Azure"

    if [ -f "/usr/local/bin/azureService.sh" ]; then 
        /usr/local/bin/azureService.sh 
    else 
        echo "[ERROR] azureService.sh not found" 
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
    echo "[INFO] Skipping .env variable extraction"
fi

echo "===== APP VERSION ====="
echo -e "[INFO] APP_VERSION: ${APP_VERSION:-N/A} \n"

export NEW_RELIC_ENABLE NEWRELICLICENSEKEY

# =========================
# NEW RELIC
# =========================

echo "===== NEW RELIC ====="

NEW_RELIC_PATH="/etc/php.d/newrelic.ini"

if [ -f "$NEW_RELIC_PATH" ]; then
    echo "[INFO] Setting up New Relic..."

    envsubst < /etc/php.d/newrelic.ini > /tmp/newrelic.ini
    mv /tmp/newrelic.ini /etc/php.d/newrelic.ini    

    pkill newrelic-daemon || true
    newrelic-daemon --logfile /var/log/newrelic/newrelic-daemon.log --port @newrelic
else
    echo "[WARN] New Relic is disabled."
fi
echo "";

# =========================
# XDEBUG
# =========================

echo "===== XDEBUG ====="

XDEBUG_PATH="/etc/php.d/99-xdebug.ini"

if [ -f "$XDEBUG_PATH" ]; then
    echo "[DEBUG] Xdebug is enabled"
else
    echo "[WARN] Xdebug is disabled"
fi

echo "";

# =========================
# LARAVEL
# =========================

echo "===== CLEANUP ====="

echo "[INFO] Cleaning Laravel cache..."
rm -f bootstrap/cache/*.php

if [ "$APP_ENV" = "production" ]; then
    echo "[INFO]Optimizing Laravel... (prod)"
    php artisan config:cache || true
    php artisan route:cache || true
else
    echo "[INFO] Development mode (no cache)..."
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

echo "";

# =========================
# RUN APP
# =========================

if [ "$APP_ENV" = "production" ]; then
    echo "[INFO] Starting PHP-FPM (recommended for production)"
    exec php-fpm
else
    echo "[INFO] Starting Laravel dev server"
    exec php -d max_execution_time=30 artisan serve --host=0.0.0.0 --port=8000
fi