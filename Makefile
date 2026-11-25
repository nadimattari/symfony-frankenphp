# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec sf-php
NODE_CONT = $(DOCKER_COMP) exec -T sf-node

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console
NPM      = $(NODE_CONT) npm

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh bash composer vendor sf cc test npm-dev npm-watch npm-build npm-dev-server npm-install npm-ci npm-typecheck npm-lint npm-test

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test sf-php php bin/phpunit $(c)

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

## —— Frontend (Vue) 🌊 ——————————————————————————————————————————————————————————————
npm-dev: ## Compile assets once in development mode (sf-node)
	@$(NPM) run dev

npm-watch: ## Rebuild assets on change in sf-node (long-running)
	@$(NPM) run watch

npm-build: ## Build production assets in sf-node
	@$(NPM) run build

npm-dev-server: ## Start the Webpack dev server in sf-node (long-running)
	@$(NPM) run dev-server

npm-install: ## Install frontend dependencies in sf-node
	@$(NODE_CONT) npm install

npm-ci: ## Install frontend dependencies from the lock file in sf-node
	@$(NODE_CONT) npm ci

npm-typecheck: ## Run vue-tsc --noEmit in sf-node
	@$(NPM) run typecheck

npm-lint: ## Run ESLint/Prettier in sf-node
	@$(NPM) run lint

npm-test: ## Run frontend service/guard tests in sf-node (npm run test:fetch)
	@$(NPM) run test:fetch
