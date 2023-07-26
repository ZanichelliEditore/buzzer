FROM php:8.1-fpm

ARG USER

RUN apt-get update && apt-get install -y wget libmcrypt-dev mariadb-client openssl zip unzip git libpng-dev libjpeg62-turbo-dev libgd-dev apt-utils \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install gd \
    && pecl install xdebug-3.1.5 \
    && docker-php-ext-enable xdebug

COPY docker/php/custom-dev.d /usr/local/etc/php/conf.d

RUN mkdir -p /home/$USER
RUN groupadd -g 1000 $USER
RUN useradd -u 1000 -g $USER $USER -d /home/$USER
RUN chown $USER:$USER /home/$USER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www
USER $USER




