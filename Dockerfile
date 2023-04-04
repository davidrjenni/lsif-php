FROM composer:2 AS builder

COPY composer.json /app/
COPY composer.lock /app/
RUN composer install --no-dev --no-progress --no-interaction

FROM sourcegraph/src-cli:5 AS src-cli

FROM php:8.1-cli-alpine3.15

RUN echo 'memory_limit=1G' >> /usr/local/etc/php/conf.d/docker-php-memory-limit.ini;

COPY --from=builder /app/vendor /app/vendor
COPY bin /app/bin
COPY src /app/src

COPY --from=src-cli /usr/bin/src /usr/bin/src
RUN ln -s /app/bin/lsif-php /usr/bin/lsif-php
RUN apk add --no-cache git

WORKDIR /src
