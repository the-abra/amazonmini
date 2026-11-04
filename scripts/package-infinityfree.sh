#!/usr/bin/env bash
set -e

# scripts/package-infinityfree.sh
# Packages the application ready for extraction into InfinityFree htdocs/ directory.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_ZIP="/tmp/amazon-mini-infinityfree.zip"

echo "==> Creating clean InfinityFree deployment zip..."
rm -f "$OUTPUT_ZIP"

python3 - << EOF
import os
import zipfile

root_dir = "$ROOT_DIR"
output_zip = "$OUTPUT_ZIP"

include_dirs = ['app', 'assets', 'config', 'database', 'docs']
include_files = ['index.php', 'LICENSE', 'README.md']

with zipfile.ZipFile(output_zip, 'w', zipfile.ZIP_DEFLATED) as zf:
    for f in include_files:
        path = os.path.join(root_dir, f)
        if os.path.exists(path):
            zf.write(path, f)

    for d in include_dirs:
        dir_path = os.path.join(root_dir, d)
        for dirpath, _, filenames in os.walk(dir_path):
            for file in filenames:
                if (file.startswith('.') and file != '.htaccess') or file.endswith('.local.php') or file.endswith('.log'):
                    continue
                full_path = os.path.join(dirpath, file)
                rel_path = os.path.relpath(full_path, root_dir)
                zf.write(full_path, rel_path)

print("[✓] Deployment package successfully created: " + output_zip)
EOF

echo "    Size: $(du -h "$OUTPUT_ZIP" | cut -f1)"
echo "    To deploy: Extract the contents of this zip directly into your InfinityFree 'htdocs/' folder."
echo "    See docs/INFINITYFREE_DEPLOY.md for full step-by-step instructions."
