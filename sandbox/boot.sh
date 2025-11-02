#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SANDBOX_DIR="$SCRIPT_DIR"
APP_DIR="$SANDBOX_DIR/laravel-app"

if [ ! -d "$APP_DIR" ]; then
  echo "Creating Laravel app in $APP_DIR"
  composer create-project --prefer-dist laravel/laravel "$APP_DIR"
else
  echo "Reusing existing Laravel app in $APP_DIR"
fi

(
  cd "$APP_DIR"

  composer config repositories.glugox-core --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/core\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-module --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/module\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-inventory --json "{\"type\":\"path\",\"url\":\"$SANDBOX_DIR/modules/inventory\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config minimum-stability dev
  composer config prefer-stable true
  composer require glugox/core:dev-main glugox/module:dev-main glugox/inventory:dev-main --no-interaction

  echo "Starting Laravel development server on http://127.0.0.1:8000"
  exec php artisan serve --host=127.0.0.1 --port=8000
)
