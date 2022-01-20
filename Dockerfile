FROM composer:2 AS builder

COPY composer.json /app/
COPY composer.lock /app/
RUN composer install --no-dev --no-progress --no-interaction

FROM sourcegraph/src-cli:3 AS src-cli

FROM php:8.1-cli-alpine3.15

COPY --from=builder /app/vendor /app/vendor
COPY bin /app/bin
COPY src /app/src

COPY --from=src-cli /usr/bin/src /usr/bin/src
RUN ln -s /app/bin/lsif-php /usr/bin/lsif-php
RUN apk add --no-cache git

WORKDIR /src