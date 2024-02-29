FROM php:8.3-apache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Default working directory is /var/www/html

RUN apt-get update \
    && apt-get install -y libzip-dev \
    && docker-php-ext-install zip \
    && a2enmod rewrite

# Set DocumentRoot to /var/www/html/public
#
# Taken from https://hub.docker.com/_/php
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

# Dependencies
COPY composer.json composer.lock .env ./
COPY bin/ bin/
# We can also run composer as user `www-data`, then we don't need to set the envvar below
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

COPY . ./

# App environment needs to be prod for the following commands to succeed
ENV APP_ENV=prod
RUN php bin/console importmap:install -n \
    && php bin/console asset-map:compile -n

# Apache2 runs as `www-data` by default.
#
# We make the var/ directory writable. See also:
# https://symfony.com/doc/current/setup/file_permissions.html
RUN chown -R www-data:www-data var
