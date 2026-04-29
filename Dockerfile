# =========================
# ARG GLOBAL
# =========================
ARG APP_ENV=local

# =========================
# STAGE 1: BUILD
# =========================
FROM registry.access.redhat.com/ubi9/php-83 AS builder

ARG APP_ENV
ENV APP_ENV=${APP_ENV}

WORKDIR /app
USER root

RUN yum -y update && \
    yum -y install git zip unzip && \
    yum clean all

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock ./

# Composer by environment
RUN if [ "$APP_ENV" = "production" ]; then \
    composer install \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
        --no-scripts; \
else \
    composer install --no-scripts; \
fi

COPY . .

# Limpiar cache compilado (importante)
RUN rm -f bootstrap/cache/*.php

# NO ejecutar artisan en build

# =========================
# STAGE 2: RUNTIME
# =========================
FROM registry.access.redhat.com/ubi9/php-83

ARG APP_ENV
ENV APP_ENV=${APP_ENV}

WORKDIR /var/www/html
USER root

RUN yum -y update && \
    yum -y install unzip jq php-pear php-devel gcc make autoconf && \
    yum clean all

RUN mkdir -p /tmp && chmod 1777 /tmp

# =========================
# GLOBAL VARIABLES
# =========================
ENV ENABLE_NEW_RELIC=false \
    ENABLE_XDEBUG=true \
    PHP_MEMORY_LIMIT=512M \
    APP_ENV=${APP_ENV}

# =========================
# Temporary settings
# =========================
COPY newrelic.ini /tmp/newrelic.ini
COPY xdebug.ini /tmp/xdebug.ini

# =========================
# NEW RELIC
# =========================
RUN if [ "$ENABLE_NEW_RELIC" = "true" ]; then \
    echo "Installing New Relic..." && \
    curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-12.6.0.34-linux.tar.gz \
    | tar -C /tmp -zx && \
    NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 /tmp/newrelic-php5-*/newrelic-install install && \
    mv /tmp/newrelic.ini /etc/php.d/newrelic.ini; \
else \
    echo "New Relic disabled" && \
    rm -f /tmp/newrelic.ini; \
fi

# =========================
# XDEBUG
# =========================
RUN if [ "$ENABLE_XDEBUG" = "true" ]; then \
    echo "Installing Xdebug..." && \
    pecl install xdebug && \
    echo "zend_extension=$(find /usr/lib64/php/modules/ -name xdebug.so)" > /etc/php.d/15-xdebug.ini && \
    mv /tmp/xdebug.ini /etc/php.d/99-xdebug.ini && \
    rm -f /etc/php.d/15-xdebug.ini; \
else \
    echo "Xdebug is disabled" && \
    rm -f /tmp/xdebug.ini; \
fi

# Clean up build tools
RUN yum -y remove gcc make autoconf php-devel && \
    yum clean all

# =========================
# Copy app
# =========================
COPY --from=builder /app /tmp/app

RUN if [ "$APP_ENV" = "production" ]; then \
    echo "Copying code to $APP_ENV" && \
    cp -r /tmp/app/. /var/www/html && \
    chgrp -R 0 /var/www/html && \
    chmod -R g+rwX /var/www/html; \
else \
    echo "Development mode: volume will be used"; \
fi

# =========================
# Permissions
# =========================
RUN chown -R 1001:0 /var/www/html && \
    chmod -R g+rw /var/www/html && \
    chmod -R 775 storage bootstrap/cache || true

# =========================
# Entrypoint
# =========================
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER 1000

EXPOSE 8000

CMD ["/usr/local/bin/entrypoint.sh"]