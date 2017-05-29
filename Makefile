all: deploy

run-install = composer install --no-interaction --prefer-dist --ignore-platform-reqs $(1)
run-update = composer update --no-interaction --prefer-dist --ignore-platform-reqs $(1)
run-script = composer run-script $(1)
run-database-fixtures = php app/console doctrine:fixtures:load -n $(1)
create-file = \
	if [ ! -f $(1) ]; then echo $(2) >> $(1); fi


define APP_CONFIG
<?php
$$parameters = array(
	'db_driver' => 'pdo_mysql',
	'db_host' => '$(MAUTIC_DB_HOST)',
	'db_name' => '$(MAUTIC_DB_NAME)',
	'db_user' => '$(MAUTIC_DB_USER)',
	'db_password' => '$(MAUTIC_DB_PASSWORD)',
	'trusted_proxies' => ["$(MAUTIC_TRUSTED_PROXIE)"],
	'install_source' => 'Docker',
);
endef

export APP_CONFIG

# Test

.PHONY: test
test: build-config-if-not-exists
	bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist app/bundles

# Development

.PHONY: development
development: fix build-config-if-not-exists clear composer-dev permissions

### Deploy

.PHONY: deploy
deploy: build-config-if-not-exists permissions

# Build

.PHONY: prepare
prepare: fix dependencies

.PHONY: build
build: fix composer clear

.PHONY: build-test
build-test: fix composer-test clear

# Tasks

.PHONY: clear
clear:
	rm -fR app/cache/* app/logs/*

.PHONY: fix
fix:
	mkdir -p .git/hooks

.PHONY: permissions
permissions:
	mkdir -p app/cache app/logs
	touch app/config/local.php
	chown -R www-data:www-data app/cache app/logs app/config/local.php media/dashboards

## Composer

.PHONY: dependencies
dependencies:
	$(call run-install)

.PHONY: composer
composer:
	$(call run-install, --optimize-autoloader --no-dev --no-scripts)

.PHONY: composer-dev
composer-dev:
	$(call run-install, --optimize-autoloader)

.PHONY: composer-test
composer-test:
	$(call run-install, --optimize-autoloader --no-scripts)

.PHONY: composer-update-dev
composer-update-dev:
	$(call run-update, --optimize-autoloader)

## Others

.PHONY: build-config-if-not-exists
build-config-if-not-exists:
	$(call create-file, app/config/local.php, "$$APP_CONFIG")
