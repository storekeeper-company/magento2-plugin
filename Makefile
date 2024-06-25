.PHONY: format test test-build test-clean test-unit test-integration test-bash
PHP_VERSION?=8.1

DOCKER_COMPOSE_CMD=PHP_VERSION=$(PHP_VERSION)  docker compose


## ---- Testing ----------------------------------------
test-clean:
	$(DOCKER_COMPOSE_CMD) down --volumes

test-build:
	$(DOCKER_COMPOSE_CMD) build test

test-unit: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test /magento-plugin/docker/run-unit-test

test-integration: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test /magento-plugin/docker/run-integration-test

test: test-unit test-integration

test-bash: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test bash

test-install: check-magento-keys test-build
	$(DOCKER_COMPOSE_CMD) run --rm test /magento-plugin/docker/install.sh

# Check Magento keys
check-magento-keys:
ifndef MAGENTO_PUBLIC
	$(error MAGENTO_PUBLIC needs to set and env variable)
endif
ifndef MAGENTO_PRIVATE
	$(error MAGENTO_PRIVATE needs to set and env variable)
endif
