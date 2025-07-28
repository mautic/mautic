# Mautic Docker Image
# Based on PHP 8.2+ Apache with all required extensions
FROM php:8.2-apache

LABEL maintainer="Mautic Community <community@mautic.org>"
LABEL org.opencontainers.image.source="https://github.com/mautic/mautic"
LABEL org.opencontainers.image.description="Mautic - Open Source Marketing Automation Platform"
LABEL org.opencontainers.image.licenses="GPL-3.0"

# Set environment variables
ENV MAUTIC_VERSION=7.x \
    MAUTIC_URL="" \
    MAUTIC_DB_HOST="db" \
    MAUTIC_DB_PORT="3306" \
    MAUTIC_DB_NAME="mautic" \
    MAUTIC_DB_USER="mautic" \
    MAUTIC_DB_PASSWORD="" \
    MAUTIC_DB_TABLE_PREFIX="" \
    MAUTIC_ADMIN_USERNAME="admin" \
    MAUTIC_ADMIN_PASSWORD="" \
    MAUTIC_ADMIN_EMAIL="admin@example.com" \
    MAUTIC_ADMIN_FIRSTNAME="Admin" \
    MAUTIC_ADMIN_LASTNAME="User" \
    PHP_MEMORY_LIMIT="512M" \
    PHP_MAX_EXECUTION_TIME="300" \
    PHP_UPLOAD_MAX_FILESIZE="256M" \
    PHP_POST_MAX_SIZE="256M"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    mariadb-client \
    cron \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        ctype \
        curl \
        gd \
        iconv \
        intl \
        json \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        simplexml \
        tokenizer \
        xml \
        zip

# Install IMAP extension (optional but recommended)
RUN apt-get update && apt-get install -y libc-client-dev libkrb5-dev \
    && PHP_OPENSSL=yes docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP
RUN { \
    echo 'memory_limit = ${PHP_MEMORY_LIMIT}'; \
    echo 'max_execution_time = ${PHP_MAX_EXECUTION_TIME}'; \
    echo 'upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}'; \
    echo 'post_max_size = ${PHP_POST_MAX_SIZE}'; \
    echo 'max_input_vars = 3000'; \
    echo 'date.timezone = UTC'; \
    echo 'always_populate_raw_post_data = -1'; \
    echo 'session.auto_start = Off'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/mautic.ini

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate ssl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/var \
    && chmod -R 775 /var/www/html/config \
    && chmod -R 775 /var/www/html/media

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Generate assets
RUN php bin/console mautic:assets:generate --no-interaction || true

# Configure Apache virtual host
RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    DocumentRoot /var/www/html'; \
    echo '    <Directory /var/www/html>'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# Create entrypoint script
RUN { \
    echo '#!/bin/bash'; \
    echo 'set -e'; \
    echo ''; \
    echo '# Wait for database to be ready'; \
    echo 'until mysql -h"$MAUTIC_DB_HOST" -P"$MAUTIC_DB_PORT" -u"$MAUTIC_DB_USER" -p"$MAUTIC_DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do'; \
    echo '  echo "Waiting for database connection..."'; \
    echo '  sleep 5'; \
    echo 'done'; \
    echo ''; \
    echo '# Check if Mautic is already installed'; \
    echo 'if [ ! -f config/local.php ]; then'; \
    echo '  echo "Installing Mautic..."'; \
    echo '  # Validate required environment variables'; \
    echo '  if [ -z "$MAUTIC_ADMIN_PASSWORD" ]; then'; \
    echo '    echo "ERROR: MAUTIC_ADMIN_PASSWORD environment variable is required"'; \
    echo '    exit 1'; \
    echo '  fi'; \
    echo '  if [ -z "$MAUTIC_DB_PASSWORD" ]; then'; \
    echo '    echo "ERROR: MAUTIC_DB_PASSWORD environment variable is required"'; \
    echo '    exit 1'; \
    echo '  fi'; \
    echo '  php bin/console mautic:install \'; \
    echo '    --db_driver=pdo_mysql \'; \
    echo '    --db_host="$MAUTIC_DB_HOST" \'; \
    echo '    --db_port="$MAUTIC_DB_PORT" \'; \
    echo '    --db_name="$MAUTIC_DB_NAME" \'; \
    echo '    --db_user="$MAUTIC_DB_USER" \'; \
    echo '    --db_password="$MAUTIC_DB_PASSWORD" \'; \
    echo '    --db_table_prefix="$MAUTIC_DB_TABLE_PREFIX" \'; \
    echo '    --admin_username="$MAUTIC_ADMIN_USERNAME" \'; \
    echo '    --admin_password="$MAUTIC_ADMIN_PASSWORD" \'; \
    echo '    --admin_email="$MAUTIC_ADMIN_EMAIL" \'; \
    echo '    --admin_firstname="$MAUTIC_ADMIN_FIRSTNAME" \'; \
    echo '    --admin_lastname="$MAUTIC_ADMIN_LASTNAME" \'; \
    echo '    --site_url="$MAUTIC_URL" \'; \
    echo '    --no-interaction'; \
    echo 'else'; \
    echo '  echo "Mautic already installed, updating database schema..."'; \
    echo '  php bin/console doctrine:schema:update --force --no-interaction || true'; \
    echo 'fi'; \
    echo ''; \
    echo '# Warm up cache'; \
    echo 'php bin/console cache:warmup --no-interaction'; \
    echo ''; \
    echo '# Set proper permissions'; \
    echo 'chown -R www-data:www-data /var/www/html'; \
    echo 'chmod -R 775 /var/www/html/var /var/www/html/config /var/www/html/media'; \
    echo ''; \
    echo '# Start Apache'; \
    echo 'exec apache2-foreground'; \
} > /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Create cron configuration for Mautic
RUN { \
    echo '# Mautic cron jobs'; \
    echo '*/5 * * * * www-data php /var/www/html/bin/console mautic:segments:update --batch-limit=900 --max-contacts=300'; \
    echo '*/5 * * * * www-data php /var/www/html/bin/console mautic:campaigns:update --batch-limit=100'; \
    echo '*/5 * * * * www-data php /var/www/html/bin/console mautic:campaigns:trigger --batch-limit=100'; \
    echo '10 2 * * * www-data php /var/www/html/bin/console mautic:emails:send --message-limit=200'; \
    echo '0 4 * * * www-data php /var/www/html/bin/console mautic:maintenance:cleanup --days-old=365 --dry-run=false'; \
    echo '5 1 * * 0 www-data php /var/www/html/bin/console mautic:iplookup:download'; \
} > /etc/cron.d/mautic && chmod 0644 /etc/cron.d/mautic

# Create supervisor configuration
RUN { \
    echo '[supervisord]'; \
    echo 'nodaemon=true'; \
    echo 'user=root'; \
    echo ''; \
    echo '[program:apache2]'; \
    echo 'command=/usr/local/bin/docker-entrypoint.sh'; \
    echo 'stdout_logfile=/dev/stdout'; \
    echo 'stdout_logfile_maxbytes=0'; \
    echo 'stderr_logfile=/dev/stderr'; \
    echo 'stderr_logfile_maxbytes=0'; \
    echo ''; \
    echo '[program:cron]'; \
    echo 'command=/usr/sbin/cron -f'; \
    echo 'autostart=true'; \
    echo 'autorestart=true'; \
    echo 'stdout_logfile=/dev/stdout'; \
    echo 'stdout_logfile_maxbytes=0'; \
    echo 'stderr_logfile=/dev/stderr'; \
    echo 'stderr_logfile_maxbytes=0'; \
} > /etc/supervisor/conf.d/mautic.conf

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5m --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port
EXPOSE 80

# Set volumes for persistent data
VOLUME ["/var/www/html/config", "/var/www/html/var", "/var/www/html/media"]

# Start supervisor (which starts Apache and Cron)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
