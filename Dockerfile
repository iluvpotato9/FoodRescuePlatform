FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libsqlite3-dev libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

EXPOSE 8000
