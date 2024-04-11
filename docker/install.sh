#!/bin/bash
set -euox pipefail

source /magento-plugin/docker/env/blackfire.env
source /magento-plugin/docker/env/db.env
source /magento-plugin/docker/env/elasticsearch.env
source /magento-plugin/docker/env/magento.env
source /magento-plugin/docker/env/opensearch.env
source /magento-plugin/docker/env/phpfpm.env
source /magento-plugin/docker/env/rabbitmq.env
source /magento-plugin/docker/env/redis.env

composer config --global http-basic.repo.magento.com "$MAGENTO_PUBLIC" "$MAGENTO_PRIVATE"

composer create-project --repository-url=https://repo.magento.com/ \
     "${META_PACKAGE}" /tmp/exampleproject/ "${META_VERSION}"

rsync -a /tmp/exampleproject/ $MAGENTO_DIR
 rm -rf /tmp/exampleproject/

composer config --no-plugins allow-plugins.magento/magento-composer-installer true
composer config --no-plugins allow-plugins.magento/inventory-composer-installer true
composer config --no-plugins allow-plugins.laminas/laminas-dependency-plugin true

bin/magento setup:install \
  --db-host="$MYSQL_HOST" \
  --db-name="$MYSQL_DATABASE" \
  --db-user="$MYSQL_USER" \
  --db-password="$MYSQL_PASSWORD" \
  --base-url=http://localhost:9442/ \
  --base-url-secure=https://localhost:9442/ \
  --backend-frontname="$MAGENTO_ADMIN_FRONTNAME" \
  --admin-firstname="$MAGENTO_ADMIN_FIRST_NAME" \
  --admin-lastname="$MAGENTO_ADMIN_LAST_NAME" \
  --admin-email="$MAGENTO_ADMIN_EMAIL" \
  --admin-user="$MAGENTO_ADMIN_USER" \
  --admin-password="$MAGENTO_ADMIN_PASSWORD" \
  --language="$MAGENTO_LOCALE" \
  --currency="$MAGENTO_CURRENCY" \
  --timezone="$MAGENTO_TIMEZONE" \
  --amqp-host="$RABBITMQ_HOST" \
  --amqp-port="$RABBITMQ_PORT" \
  --amqp-user="$RABBITMQ_DEFAULT_USER" \
  --amqp-password="$RABBITMQ_DEFAULT_PASS" \
  --amqp-virtualhost="$RABBITMQ_DEFAULT_VHOST" \
  --cache-backend=redis \
  --cache-backend-redis-server="$REDIS_CACHE_BACKEND_SERVER" \
  --cache-backend-redis-db="$REDIS_CACHE_BACKEND_DB" \
  --page-cache=redis \
  --page-cache-redis-server="$REDIS_PAGE_CACHE_SERVER" \
  --page-cache-redis-db="$REDIS_PAGE_CACHE_DB" \
  --session-save=redis \
  --session-save-redis-host="$REDIS_SESSION_SAVE_HOST" \
  --session-save-redis-log-level=4 \
  --session-save-redis-db=2 \
  --elasticsearch-host="$ES_HOST" \
  --elasticsearch-port="$ES_PORT" \
  --opensearch-host="$OPENSEARCH_HOST" \
  --opensearch-port="$OPENSEARCH_PORT" \
  --search-engine=elasticsearch7 \
  --use-rewrites=1 \
  --no-interaction

composer require markshust/magento2-module-disabletwofactorauth
bin/magento module:enable MarkShust_DisableTwoFactorAuth
bin/magento config:set twofactorauth/general/enable 0

jq '.repositories += [{"type": "path", "url": "/magento-plugin/", "options": {"symlink": false}}]' $MAGENTO_DIR/composer.json > $MAGENTO_DIR/composer.json.tmp
mv $MAGENTO_DIR/composer.json.tmp $MAGENTO_DIR/composer.json

composer require "storekeeper/magento2-plugin @dev" &&\
bin/magento setup:upgrade &&\
bin/magento setup:di:compile &&\
bin/magento setup:static-content:deploy -f &&\
bin/magento cache:clean

mysql -h"${MYSQL_INTEGRATION_HOST}" -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_INTEGRATION_DATABASE}" -e exit &> /dev/null ||
  mysqladmin -h"${MYSQL_INTEGRATION_HOST}" -uroot -p"${MYSQL_ROOT_PASSWORD}" create "${MYSQL_INTEGRATION_DATABASE}" &&
  echo "Database ${MYSQL_INTEGRATION_DATABASE} created." &&
  mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -h"${MYSQL_INTEGRATION_HOST}" \
    -e "GRANT ALL PRIVILEGES ON ${MYSQL_INTEGRATION_DATABASE}.* TO '${MYSQL_INTEGRATION_USER}'@'%';FLUSH PRIVILEGES;"

cp /magento-plugin/docker/template/dev/tests/integration/etc/install-config-mysql.php $MAGENTO_DIR/dev/tests/integration/etc/install-config-mysql.php

bin/magento module:enable Magento_AdminAdobeImsTwoFactorAuth
bin/magento module:enable Magento_TwoFactorAuth