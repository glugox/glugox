#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SANDBOX_DIR="$SCRIPT_DIR"
APP_DIR="$SANDBOX_DIR/laravel-app"

rm -rf "$APP_DIR"

composer create-project --prefer-dist laravel/laravel "$APP_DIR"

(
  cd "$APP_DIR"
  composer config repositories.glugox-core '{"type":"path","url":"'"$REPO_ROOT"'/packages/glugox/core","options":{"symlink":true}}'
  composer config repositories.glugox-module '{"type":"path","url":"'"$REPO_ROOT"'/packages/glugox/module","options":{"symlink":true}}'
  composer require glugox/core:* glugox/module:* --no-interaction
)
