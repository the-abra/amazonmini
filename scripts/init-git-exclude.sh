#!/usr/bin/env bash
set -e

# scripts/init-git-exclude.sh
# Configures .git/info/exclude to maintain zero dotfiles in repository root.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
EXCLUDE_FILE="$ROOT_DIR/.git/info/exclude"

if [ ! -d "$ROOT_DIR/.git" ]; then
    echo "[!] Git repository not initialized. Initializing git..."
    git -C "$ROOT_DIR" init
fi

mkdir -p "$ROOT_DIR/.git/info"

cat << 'EOF' > "$EXCLUDE_FILE"
# Educational E-Commerce Project Local Exclusions
# Kept in .git/info/exclude to maintain a strictly clean repository root (no .gitignore)

# Local configuration overrides (InfinityFree / local credentials)
config/config.local.php

# Docker volumes and temp data
backups/
docker/data/
docker/*.log
*.log

# Build artifacts & deployment packages
*.zip
dist/
tmp/

# OS-generated files
.DS_Store
Thumbs.db
EOF

echo "[✓] Configured .git/info/exclude successfully."
