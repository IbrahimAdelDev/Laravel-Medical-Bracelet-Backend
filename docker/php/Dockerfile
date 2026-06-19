FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN groupadd -g 1000 ibrahimadel && \
    useradd -m -u 1000 -g 1000 -s /bin/bash ibrahimadel

# RUN groupadd -g ${GID} ibrahimadel && \
# useradd -m -u ${UID} -g ${GID} -s /bin/bash ibrahimadel

WORKDIR /var/www

RUN chown -R ibrahimadel:ibrahimadel /var/www

# RUN chown -R www-data:www-data /var/www