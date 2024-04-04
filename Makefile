.PHONY: format test test-build test-clean test-unit test-integration test-bash
PHP_VERSION?=8.2

DOCKER_COMPOSE_CMD=PHP_VERSION=$(PHP_VERSION)  docker compose


## ---- Testing ----------------------------------------
test-clean:
	$(DOCKER_COMPOSE_CMD) down --volumes

test-build:
	$(DOCKER_COMPOSE_CMD) build test

test-unit: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test run-unit-test

test-integration: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test run-integration-test

test: test-unit test-integration

test-bash: test-build
	$(DOCKER_COMPOSE_CMD) run --rm test bash

test-install: check-magento-keys test-build
	$(DOCKER_COMPOSE_CMD) run --rm test /magento-plugin/docker/install.sh

# Check Magento keys
check-magento-keys:
ifndef MAGENTO_PUBLIC
	$(error MAGENTO_PUBLIC is undefined)
endif
ifndef MAGENTO_PRIVATE
	$(error MAGENTO_PRIVATE is undefined)
endif
