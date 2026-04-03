ifneq ($(MAKECMDGOALS),dotenv-dump)
-include .env
export
endif

DOCKER_COMPOSE_CMD = docker-compose

DOCKER_COMPOSE = -f compose.yaml
ifeq ($(DOCKER_COMPOSE_ENV),dev)
	DOCKER_COMPOSE := $(DOCKER_COMPOSE) -f compose.dev.yaml
endif

DOCKER_COMPOSE := $(DOCKER_COMPOSE_CMD) $(DOCKER_COMPOSE)

.PHONY: help

help: ## Displays help for a command
	@printf "\033[33mUsage:\033[0m\n  make [options] [target] ...\n\n\033[33mAvailable targets:%-13s\033[0m\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' 'Makefile' | awk 'BEGIN {FS = ":.*?## "}; {printf "%-2s\033[32m%-20s\033[0m %s\n", "", $$1, $$2}'

start: up ## Start the application

stop: down ## Stop the application

build: ## Build images
	$(DOCKER_COMPOSE) build --force-rm

pull: ## Pull images
	$(DOCKER_COMPOSE) pull --ignore-buildable

up: ## Start containers
	$(DOCKER_COMPOSE) up --detach --remove-orphans --force-recreate
	$(DOCKER_COMPOSE) ps

down: ## Stop and remove containers
	$(DOCKER_COMPOSE) down

db-init-test: ## Create test database
	$(DOCKER_COMPOSE) exec db psql -U app -d noto -c "CREATE DATABASE noto_test"

logs: ## Show containers logs
	$(DOCKER_COMPOSE) logs -f

dotenv-dump: ## Merge envs. Arguments: src=<source file> dest=<destination file>
	@[ "$(src)" ] || (echo "Please specify src=<file>"; exit 1)
	@[ "$(dest)" ] || (echo "Please specify dest=<file>"; exit 1)
	@[ -f "$(src)" ] || (echo "File '$(src)' not found"; exit 1)
	printenv | awk '/^[^#].+$$/ {sub(/=/," ");c[$$1]++;if(2==c[$$1]){print $$1"="$$2}}' \
		$(src) - $(src) > $(dest)

test: ## Run all tests
	$(DOCKER_COMPOSE) exec app bin/phpunit --display-phpunit-notices

test-unit: ## Run unit tests only
	$(DOCKER_COMPOSE) exec app bin/phpunit tests/Unit

test-integration: ## Run integration tests only
	$(DOCKER_COMPOSE) exec app bin/phpunit tests/Integration

test-coverage: ## Run tests with coverage report
	$(DOCKER_COMPOSE) exec app bin/phpunit --coverage-html ./coverage
	@echo "Coverage report generated in ./coverage/index.html"

cs: ## Check code style
	$(DOCKER_COMPOSE) exec app bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style
	$(DOCKER_COMPOSE) exec app bin/php-cs-fixer fix

phpstan: ## Run static analysis
	$(DOCKER_COMPOSE) exec app bin/phpstan analyse --memory-limit=512M
