FROM composer:2 AS composer
FROM sourcegraph/src-cli:3 AS src-cli

FROM gitpod/workspace-full
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=src-cli  /usr/bin/src      /usr/bin/src
