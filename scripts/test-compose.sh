#!/usr/bin/env bash
set -e

# scripts/test-compose.sh
# Runs full integration test suite via Docker Compose.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "==> Ensuring Git exclude is initialized..."
"$ROOT_DIR/scripts/init-git-exclude.sh"

echo "==> Starting database service..."
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" up -d db

echo "==> Waiting for MariaDB to become healthy..."
until docker compose -f "$ROOT_DIR/docker/docker-compose.yml" ps db | grep -q "healthy"; do
    sleep 1
done

echo "==> Resetting schema + seed data for deterministic tests..."
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db < "$ROOT_DIR/database/schema.sql"
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db < "$ROOT_DIR/database/seed.sql"

echo "==> Executing automated tests in Docker test container..."
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" run --rm test
