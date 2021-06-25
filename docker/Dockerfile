FROM php:8.0-fpm

RUN apt-get update

# Configure PHP extensions
RUN docker-php-ext-install -j$(nproc) pdo_mysql

# Setup Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/bin/ --filename=composer \
    chmod +x /bin/composer && rm -rf composer-setup.php

# Tweaks
WORKDIR /code