# Makefile
# Task automation for Amazon Mini Clone (Educational E-Commerce & Cargo Tracking)

.PHONY: help init build dev test seed dist stop clean migrate backup restore webhook smoke lint logs shell-db ps

help:
	@echo "Amazon Mini Clone — Available Commands:"
	@echo "  make init    - Initialize .git/info/exclude (ensures clean root directory)"
	@echo "  make build   - Build standalone Docker image"
	@echo "  make dev     - Start local development environment (MariaDB + PHP on :8080)"
	@echo "  make test    - Run automated unit, integration, and workflow tests"
	@echo "  make seed    - Re-import schema and seed data into Docker database"
	@echo "  make dist    - Package clean zip ready for InfinityFree htdocs/ upload"
	@echo "  make stop    - Stop all running project Docker containers"
	@echo "  make clean   - Stop containers and remove database data volumes"
	@echo "  make migrate - Apply pending SQL migrations (database/migrations)"
	@echo "  make backup  - Dump database to backups/*.sql.gz (keeps last 10)"
	@echo "  make restore FILE=backups/x.sql.gz - Restore a database dump"
	@echo "  make webhook EVENT=checkout.session.completed ORDER=3 - Send a signed Stripe event"
	@echo "  make smoke   - Black-box smoke tests (health, pagination, blocked dirs, headers)"
	@echo "  make lint    - php -l on every PHP file"
	@echo "  make logs    - Follow application logs"
	@echo "  make ps      - Show container status and health"
	@echo "  make shell-db - Open a MariaDB shell"

init:
	@./scripts/init-git-exclude.sh

build:
	@docker build -f docker/Dockerfile -t amazon-mini:latest .

dev: init
	@./scripts/dev-docker.sh

test: init
	@./scripts/test-compose.sh

seed:
	@docker compose -f docker/docker-compose.yml exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db < database/schema.sql
	@docker compose -f docker/docker-compose.yml exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db < database/seed.sql
	@echo "[✓] Database schema and seed data re-imported successfully."

dist:
	@./scripts/package-infinityfree.sh

stop:
	@docker compose -f docker/docker-compose.yml stop

clean:
	@docker compose -f docker/docker-compose.yml down -v

migrate:
	@./scripts/migrate.sh

backup:
	@./scripts/backup-db.sh

restore:
	@./scripts/restore-db.sh $(FILE)

webhook:
	@./scripts/simulate-webhook.sh $(or $(EVENT),checkout.session.completed) $(or $(ORDER),3)

smoke:
	@./scripts/smoke-test.sh

lint:
	@docker run --rm -v "$(CURDIR)":/app -w /app php:8.2-cli sh -c 'find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l | grep -v "^No syntax errors" || echo "[ok] No syntax errors."'

logs:
	@docker compose -f docker/docker-compose.yml logs -f app

ps:
	@docker compose -f docker/docker-compose.yml ps

shell-db:
	@docker compose -f docker/docker-compose.yml exec db mariadb -u ecom_user -pecom_pass ecommerce_db
