FROM nginx:1.25

ADD docker/buzzer.vhost.dev.conf /etc/nginx/conf.d/default.conf


WORKDIR /var/www
