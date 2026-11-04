#!/usr/bin/env bash
set -euo pipefail

# scripts/migrate.sh
# Applies database/migrations/*.sql exactly once each (tracked in the schema_migrations table).
# Usage: ./scripts/migrate.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE=(docker compose -f "$ROOT_DIR/docker/docker-compose.yml")
SQL=(exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db)

"${COMPOSE[@]}" "${SQL[@]}" -e "CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)"

for file in "$ROOT_DIR"/database/migrations/*.sql; do
    name="$(basename "$file")"
    applied="$("${COMPOSE[@]}" "${SQL[@]}" -N -e "SELECT COUNT(*) FROM schema_migrations WHERE name='$name'")"
    if [ "$applied" != "0" ]; then
        echo "[skip] $name (already applied)"
        continue
    fi
    # Fresh databases (built from schema.sql) already contain these changes.
    if [ "$name" = "001_cancel_webhooks_delivery_failures.sql" ] && \
       [ "$("${COMPOSE[@]}" "${SQL[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='stripe_events'")" != "0" ]; then
        "${COMPOSE[@]}" "${SQL[@]}" -e "INSERT INTO schema_migrations (name) VALUES ('$name')"
        echo "[skip] $name (schema.sql already up to date)"
        continue
    fi
    echo "[run ] $name"
    "${COMPOSE[@]}" "${SQL[@]}" < "$file"
    "${COMPOSE[@]}" "${SQL[@]}" -e "INSERT INTO schema_migrations (name) VALUES ('$name')"
done
echo "[ok] Migrations complete."
