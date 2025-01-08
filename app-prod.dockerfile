FROM php:8.1-fpm

RUN apt-get update && apt-get install -y wget libmcrypt-dev mariadb-client \
    xvfb libfontconfig wkhtmltopdf libxslt1-dev libgtk2.0-0 libnotify-dev libgconf-2-4 libnss3 libxss1 libasound2 gnupg libcanberra-gtk-module\
    openssl zip unzip git nano wget libaio-dev iputils-ping


RUN docker-php-ext-install pdo_mysql opcache

RUN pecl install redis
RUN docker-php-ext-enable redis

RUN apt-get install -y supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["/usr/bin/supervisord"]

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker/php/custom.d/prod-php-fpm.conf /usr/local/etc/php-fpm.d/zz-prod-php-fpm.conf
COPY docker/php/custom.d /usr/local/etc/php/conf.d

COPY --chown=www-data:www-data ./ /var/www

WORKDIR /var/www

USER www-data



