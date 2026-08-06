FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libgmp-dev \
    libwebp-dev \
    zlib1g-dev \
    libmagickwand-dev \
    imagemagick \
    libtidy-dev \
    cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        bcmath \
        gd \
        intl \
        mbstring \
        exif \
        zip \
        pcntl \
        soap \
        sockets \
        tidy \
        gmp \
        opcache \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

EXPOSE 9000
