#!/usr/bin/env bash
# Ticket P2 — rollback: git revert + migrate:rollback.
set -euo pipefail
APP_DIR="${APP_DIR:-/var/www/hoctoan}"
cd "$APP_DIR"
php artisan down || true
git revert --no-edit HEAD
php artisan migrate:rollback --step="${STEP:-1}" --force
composer install --no-dev --optimize-autoloader --no-interaction
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
