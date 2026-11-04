#!/usr/bin/env bash
set -euo pipefail

# scripts/restore-db.sh
# Restores a dump created by backup-db.sh. Usage: ./scripts/restore-db.sh backups/ecommerce_X.sql.gz

if [ $# -ne 1 ] || [ ! -f "$1" ]; then
    echo "Usage: $0 <backup.sql.gz>" >&2
    exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
read -r -p "This overwrites the current database. Continue? [y/N] " answer
[ "$answer" = "y" ] || { echo "Aborted."; exit 1; }

gunzip -c "$1" | docker compose -f "$ROOT_DIR/docker/docker-compose.yml" exec -T db mariadb -u ecom_user -pecom_pass ecommerce_db
echo "[ok] Database restored from $1"
