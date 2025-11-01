DC = docker compose

.PHONY: init up down restart crm-console risk-console migrate test-backend test-risk test-web

init:
	$(DC) pull
	$(DC) build

up:
	$(DC) up -d

down:
	$(DC) down --remove-orphans

restart: down up

crm-console:
	$(DC) exec crm-api php bin/console $(filter-out $@,$(MAKECMDGOALS))

risk-console:
	$(DC) exec risk-engine $(filter-out $@,$(MAKECMDGOALS))

migrate:
	$(DC) exec crm-api php bin/console doctrine:migrations:migrate --no-interaction

test-backend:
	$(DC) exec crm-api ./vendor/bin/pest

test-risk:
	$(DC) exec risk-engine go test ./...

test-web:
	$(DC) exec web npm run lint
