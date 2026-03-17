#!/usr/bin/env bash
set -euo pipefail

APP_OWNER_HOME="/home/peldargc"
APP_PATH="${APP_OWNER_HOME}/extraction_app"
DOCROOT_PATH="${APP_OWNER_HOME}/public_html/extraction"
RELEASE_PATH="${APP_PATH}/current"

# cPanel typically runs tasks from repository root; keep this as fallback.
DEPLOYMENT_SOURCE="${DEPLOYMENT_SOURCE:-$(pwd)}"

echo "[1/9] Preparing directories"
mkdir -p "${APP_PATH}" "${DOCROOT_PATH}"

echo "[2/9] Syncing repository to ${RELEASE_PATH}"
rm -rf "${RELEASE_PATH}"
mkdir -p "${RELEASE_PATH}"

# Keep deploy idempotent and avoid copying VCS metadata into release.
rsync -a --delete \
  --exclude ".git" \
  --exclude "node_modules" \
  --exclude ".env" \
  "${DEPLOYMENT_SOURCE}/" "${RELEASE_PATH}/"

echo "[3/9] Installing PHP dependencies"
cd "${RELEASE_PATH}"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "[4/9] Ensuring environment file"
if [[ ! -f "${RELEASE_PATH}/.env" ]]; then
  if [[ -f "${APP_PATH}/.env" ]]; then
    cp "${APP_PATH}/.env" "${RELEASE_PATH}/.env"
  else
    cp "${RELEASE_PATH}/.env.example" "${RELEASE_PATH}/.env"
  fi
fi

if [[ ! -f "${APP_PATH}/.env" ]]; then
  cp "${RELEASE_PATH}/.env" "${APP_PATH}/.env"
fi

# Keep single .env source of truth in APP_PATH for subsequent releases.
cp "${APP_PATH}/.env" "${RELEASE_PATH}/.env"

echo "[5/9] Running Laravel optimize tasks"
php artisan key:generate --force --no-interaction || true
php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[6/9] Linking writable storage"
mkdir -p "${APP_PATH}/storage"
if [[ ! -d "${APP_PATH}/storage/app" ]]; then
  rsync -a "${RELEASE_PATH}/storage/" "${APP_PATH}/storage/"
fi
rm -rf "${RELEASE_PATH}/storage"
ln -sfn "${APP_PATH}/storage" "${RELEASE_PATH}/storage"

# Keep Laravel public/storage reachable from the web docroot.
mkdir -p "${APP_PATH}/storage/app/public"
ln -sfn "${APP_PATH}/storage/app/public" "${DOCROOT_PATH}/storage"

echo "[7/9] Publishing public assets to ${DOCROOT_PATH}"
rsync -a --delete "${RELEASE_PATH}/public/" "${DOCROOT_PATH}/"

echo "[8/9] Writing front-controller bootstrap paths"
cat > "${DOCROOT_PATH}/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = '/home/peldargc/extraction_app/current/storage/framework/maintenance.php')) {
    require $maintenance;
}

require '/home/peldargc/extraction_app/current/vendor/autoload.php';

/** @var Application $app */
$app = require_once '/home/peldargc/extraction_app/current/bootstrap/app.php';

$app->handleRequest(Request::capture());
PHP

echo "[9/9] Fixing common permissions"
chmod -R ug+rw "${APP_PATH}/storage" "${RELEASE_PATH}/bootstrap/cache" || true

echo "Deployment completed successfully."
