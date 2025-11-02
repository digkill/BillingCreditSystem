FROM php:8.3-fpm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=dev \
    APP_DEBUG=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libssl-dev \
        librabbitmq-dev \
        pkg-config \
    && docker-php-ext-install -j"$(nproc)" intl opcache pdo pdo_pgsql zip \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY composer.json composer.lock symfony.lock ./
RUN composer install --prefer-dist --no-scripts --no-progress --no-ansi

COPY . .

RUN composer dump-autoload --optimize

CMD ["php-fpm"]
