.PHONY: help up down shell npm_watch composer_update

ENV ?= dev
PROJECT ?= buzzer

help:                             ## Show this help.
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'
up:                               ## Turn on container services
	docker-compose --file docker-compose.$(ENV).yml up -d
stop:                             ## Turn off container services
	docker-compose --file docker-compose.$(ENV).yml stop
down:                             ## Turn off and remove container services
	docker-compose --file docker-compose.$(ENV).yml down
build:                            ## Build container images
	docker-compose --file docker-compose.$(ENV).yml build
rebuild:                          ## Rebuild and turn on container services
	docker-compose --file docker-compose.$(ENV).yml up -d --build
shell:                            ## Open a shell con container app
	docker exec -it $(PROJECT)_app bash
seed:                            ## Open a shell con container app
	docker exec -it $(PROJECT)_app php artisan migrate:fresh --seed
composer_install:                  ## Execute composer install
	docker exec -it $(PROJECT)_app composer install
composer_update:                  ## Execute composer update
	docker exec -it $(PROJECT)_app composer update
npm_run:                          ## Execute npm run (prod or dev based on ENV param)
	docker-compose --file docker-compose.$(ENV).yml run --rm nodejs npm run $(ENV)
npm_run_build:                          ## Execute npm run (prod or dev based on ENV param)
	docker-compose --file docker-compose.$(ENV).yml run --rm nodejs npm run build
npm_install:                      ## Execute npm install package [use PACKAGE=<packageName>]
	docker-compose --file docker-compose.$(ENV).yml run --rm nodejs npm install $(PACKAGE)
run_tests:                        ## Execute phpunit
	docker exec -it $(PROJECT)_app vendor/bin/phpunit
run_coverage:                     ## Execute phpunit with coverage
	docker exec -it $(PROJECT)_app vendor/bin/phpunit --coverage-html tmp/coverage
cypress_open:                          ## Open Cypress interface
	make seed && xhost local:root && docker-compose --file docker-compose.dev.yml --file ./e2e/cy-open.yml up cypress
cypress_tests:                    ## Execute Cypress tests
	make seed && docker-compose --file docker-compose.$(ENV).yml run --rm cypress entrypoint npx cypress run
.DEFAULT_GOAL := help
