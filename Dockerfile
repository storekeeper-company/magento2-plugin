ARG PHP_VERSION=8.1
ARG THECODINGMACHINE_VERSION=v4

FROM thecodingmachine/php:${PHP_VERSION}-${THECODINGMACHINE_VERSION}-apache as test

USER root
RUN apt-get update\
    && apt-get install -y  --no-install-recommends \
      mariadb-client rsync jq \
    && rm -rf /var/lib/apt/lists/*

USER docker

COPY . /magento-plugin

ARG CONTAINER_CWD=/var/www/html

ENV MAGENTO_DIR=$CONTAINER_CWD \
    STOREKEEPER_PLUGIN_DIR=$CONTAINER_CWD/vendor/storekeeper/magento2-plugin
