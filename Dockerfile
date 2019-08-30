FROM composer as vendor
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist

FROM php:7.3-apache-stretch
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="1024" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
RUN apt-get update && apt-get install -y zlib1g-dev libzip-dev
RUN docker-php-source extract && docker-php-ext-install zip pdo pdo_mysql pcntl bcmath opcache && docker-php-source delete
RUN a2enmod rewrite
COPY .docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY .docker/000-default.conf /etc/apache2/sites-enabled
COPY . /var/www/html
COPY --from=vendor /app/vendor/ /var/www/html/vendor/
