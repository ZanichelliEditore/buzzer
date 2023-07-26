ARG NODE_VERSION

FROM node:${NODE_VERSION}

RUN mkdir -p /var/www
VOLUME ["/var/www"]

WORKDIR /var/www
