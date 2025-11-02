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

php "$SANDBOX_DIR/build-modules.php"

(
  cd "$APP_DIR"

  composer config repositories.glugox-core --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/core\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-module --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/module\",\"options\":{\"symlink\":true,\"canonical\":false}}"

  modules_to_require=("glugox/core:dev-main" "glugox/module:dev-main")

  if [ -d "$SANDBOX_DIR/modules" ]; then
    for module_path in "$SANDBOX_DIR"/modules/*; do
      if [ -d "$module_path" ] && [ -f "$module_path/composer.json" ]; then
        module_slug="$(basename "$module_path")"
        repo_name="glugox-${module_slug}"
        printf -v repo_json '{"type":"path","url":"%s","options":{"symlink":true,"canonical":false}}' "$module_path"
        composer config "repositories.${repo_name}" --json "$repo_json"
        modules_to_require+=("glugox/${module_slug}:dev-main")
      fi
    done
  fi

  composer config minimum-stability dev
  composer config prefer-stable true
  composer require "${modules_to_require[@]}" --no-interaction

  echo "Starting Laravel development server on http://127.0.0.1:8000"
  exec php artisan serve --host=127.0.0.1 --port=8000
)
