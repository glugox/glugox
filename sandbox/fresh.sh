#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"

if [ ! -d "$APP_DIR" ]; then
  echo "Laravel app not found in $APP_DIR. Run sandbox/boot.sh first." >&2
  exit 1
fi

php "$SCRIPT_DIR/build-modules.php"

(
  cd "$APP_DIR"

  composer config repositories.glugox-core --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/core\",\"options\":{\"symlink\":true,\"canonical\":false}}"
  composer config repositories.glugox-module --json "{\"type\":\"path\",\"url\":\"$REPO_ROOT/packages/glugox/module\",\"options\":{\"symlink\":true,\"canonical\":false}}"

  modules_to_update=(glugox/core glugox/module)

  if [ -d "$SCRIPT_DIR/modules" ]; then
    for module_path in "$SCRIPT_DIR"/modules/*; do
      if [ -d "$module_path" ]; then
        module_slug="$(basename "$module_path")"
        repo_name="glugox-${module_slug}"
        printf -v repo_json '{"type":"path","url":"%s","options":{"symlink":true,"canonical":false}}' "$module_path"
        composer config "repositories.${repo_name}" --json "$repo_json"
        modules_to_update+=("glugox/${module_slug}")
      fi
    done
  fi

  composer config minimum-stability dev
  composer config prefer-stable true

  composer update "${modules_to_update[@]}" --no-interaction

  php artisan glugox:fresh
)
