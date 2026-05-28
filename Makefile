.PHONY: help up down build logs shell-backend shell-frontend migrate fresh test lint stan pint

help:
	@echo "Skontro development commands:"
	@echo "  make up              Start all services"
	@echo "  make down            Stop all services"
	@echo "  make build           Rebuild images"
	@echo "  make logs            Tail all logs"
	@echo "  make shell-backend   Open a shell in the backend container"
	@echo "  make shell-frontend  Open a shell in the frontend container"
	@echo "  make migrate         Run database migrations"
	@echo "  make fresh           Drop and re-migrate the database"
	@echo "  make test            Run backend and frontend test suites"
	@echo "  make lint            Run frontend lint"
	@echo "  make stan            Run PHPStan"
	@echo "  make pint            Run Laravel Pint"

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f

shell-backend:
	docker compose exec backend sh

shell-frontend:
	docker compose exec frontend sh

migrate:
	docker compose exec backend php artisan migrate

fresh:
	docker compose exec backend php artisan migrate:fresh --seed

test:
	docker compose exec backend ./vendor/bin/pest
	docker compose exec frontend npm run test

lint:
	docker compose exec frontend npm run lint

stan:
	docker compose exec backend ./vendor/bin/phpstan analyse

pint:
	docker compose exec backend ./vendor/bin/pint
