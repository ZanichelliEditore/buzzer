FROM nginx:1.10

ADD docker/vhost-prod.conf /etc/nginx/conf.d/default.conf

ADD certs/ssl_certificate.crt /etc/nginx/ssl_certificate.crt
ADD certs/ssl_certificate.key /etc/nginx/ssl_certificate.key

WORKDIR /var/www
