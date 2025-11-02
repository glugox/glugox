#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"

if [ ! -d "$APP_DIR" ]; then
  echo "Laravel app not found in $APP_DIR. Run sandbox/boot.sh first." >&2
  exit 1
fi

(
  cd "$APP_DIR"

  composer config repositories.glugox-core --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/core\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-module --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/module\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-inventory --json "{\"type\":\"path\",\"url\":\"$SCRIPT_DIR/modules/inventory\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config minimum-stability dev
  composer config prefer-stable true

  composer update glugox/core glugox/module glugox/inventory --no-interaction

  php artisan glugox:fresh
)
