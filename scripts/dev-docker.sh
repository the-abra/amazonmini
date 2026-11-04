#!/usr/bin/env bash
set -e

# scripts/dev-docker.sh
# Starts local development stack with MariaDB and PHP Apache on port 8080.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "==> Initializing git exclude rules..."
"$ROOT_DIR/scripts/init-git-exclude.sh"

echo "==> Starting Amazon Mini containers (App: http://localhost:8080 | phpMyAdmin: http://localhost:8081)..."
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" up -d db app phpmyadmin

echo "==> Waiting for MariaDB healthcheck..."
docker compose -f "$ROOT_DIR/docker/docker-compose.yml" exec -T db sh -c 'until mariadb-admin ping -h 127.0.0.1 -u ecom_user -pecom_pass --silent; do sleep 1; done'

echo "==> Application is ready!"
echo "    Storefront: http://localhost:8080"
echo "    phpMyAdmin: http://localhost:8081"
echo "    (Terminal output is filtered for clean development. Press Ctrl+C to detach)"
echo ""

docker compose -f "$ROOT_DIR/docker/docker-compose.yml" logs -f app
