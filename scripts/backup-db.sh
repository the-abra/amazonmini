#!/usr/bin/env bash
set -euo pipefail

# scripts/backup-db.sh
# Dumps the database to backups/ecommerce_YYYYmmdd_HHMMSS.sql.gz and keeps the 10 most recent.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/backups"
mkdir -p "$OUT_DIR"
FILE="$OUT_DIR/ecommerce_$(date +%Y%m%d_%H%M%S).sql.gz"

docker compose -f "$ROOT_DIR/docker/docker-compose.yml" exec -T db \
    mariadb-dump -u ecom_user -pecom_pass --single-transaction --routines ecommerce_db | gzip > "$FILE"

ls -1t "$OUT_DIR"/ecommerce_*.sql.gz | tail -n +11 | xargs -r rm --
echo "[ok] Backup written: $FILE ($(du -h "$FILE" | cut -f1))"
