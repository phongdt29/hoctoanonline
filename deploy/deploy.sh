#!/usr/bin/env bash
# Ticket P2 — deploy script (KHONG build JS, theo SPEC §7).
# Chay tren VPS moi lan release. Idempotent, an toan lap lai.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/hoctoan}"
cd "$APP_DIR"

echo "==> Bat maintenance mode"
php artisan down --render="errors::503" || true

echo "==> Keo code moi"
git pull origin main

echo "==> Cai dependency (production, khong dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrate DB"
php artisan migrate --force

echo "==> Cache config/route/view"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restart queue worker (nhan code moi)"
php artisan queue:restart

echo "==> Tat maintenance mode"
php artisan up

echo "==> Deploy xong. Kiem tra /healthz"
curl -fsS http://127.0.0.1/healthz || echo "CANH BAO: healthz khong 200"
