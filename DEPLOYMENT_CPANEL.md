# cPanel Deployment (extraction.peldargconsulting.com)

This repo contains:
- GitHub Actions extractor + aggregation scripts at the repo root
- The Laravel “extraction platform” app at the repo root

The Laravel app must be deployed with the **web document root pointing at its `public/`** directory.

## Option A (recommended): set subdomain document root to Laravel `public/`

In cPanel:
1. Create (or edit) the subdomain `extraction.peldargconsulting.com`.
2. Set the document root to:
   - `.../public`

Then upload / deploy the repo so that the folder structure on the server matches.

## Server prerequisites

- PHP version compatible with the app (Laravel 12 typically expects PHP 8.2+).
- Composer available (cPanel “Terminal” or SSH strongly recommended).
- A database and credentials (MySQL/MariaDB) for Laravel.

## One-time install steps (first deploy)

Run these commands from the repo root (adjust paths if you deploy only the app folder):

```bash
cd /path/to/repo

# Install PHP deps
composer install --no-dev --optimize-autoloader

# Install/build frontend assets (only needed if you are not committing built assets)
# npm ci
# npm run build

# Set permissions (paths vary by host)
php artisan storage:link || true
php artisan optimize:clear

# Create app key (only once, and only if APP_KEY is empty)
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache config/routes for prod
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Environment variables

Configure these in `.env` (do not commit `.env`). Minimum set:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://extraction.peldargconsulting.com`
- `APP_KEY=base64:...`

Database:
- `DB_CONNECTION=mysql`
- `DB_HOST=...`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

GitHub dispatch (server-side):
- `GITHUB_PAT=...`
- `GITHUB_DISPATCH_REPO=nofiupelumi/peldarg_consulting_ext` (or your target repo)

CI → server authentication (used by `/api/github/upload-results` and `/api/github/callback`):
- `RESULT_UPLOAD_TOKEN=...` (Bearer token for both endpoints)
- `CALLBACK_HMAC_SECRET=...` (HMAC secret required by `/api/github/callback`)

Notes:
- The app reads these via `config/services.php` under `services.extractor`.
- Backward-compatible names also work: `EXTRACTOR_BEARER_TOKEN`, `EXTRACTOR_CALLBACK_SECRET`.

## Webserver notes

- Ensure `.htaccess` is honored (Apache + AllowOverride). The app’s `.htaccess` lives in `public/.htaccess`.
- If your host uses Nginx, you’ll need an equivalent rewrite to route all requests to `public/index.php`.

## Option B: if cPanel cannot point document root into `public/`

If your subdomain document root is fixed (e.g. `public_html/extraction`) and cannot be set to the Laravel `public/` directory:

- Place the contents of `peldargconsulting-extractioncredit_app/public/` into that document root.
- Keep the rest of the Laravel app **outside** the web root.
- Update the deployed `index.php` to correctly reference the app bootstrap path.

(If you want this layout, ask and I’ll provide the exact `index.php` path edits for your server’s directory structure.)

## Post-deploy checklist

- Visit `https://extraction.peldargconsulting.com/` (login page / welcome).
- Visit `https://extraction.peldargconsulting.com/admin` after logging in as an admin.
- Run the pipeline smoke test locally (or trigger a GitHub Actions run) to confirm:
  - `/api/github/upload-results` accepts token + correct `request_id`
  - `/api/github/callback` requires token + HMAC signature + correct `request_id`
  - callback idempotency works (double-callback does not double-spend credits)
