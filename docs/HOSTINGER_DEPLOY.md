# Hostinger Deployment SOP

This is the working deployment SOP for this CRM on Hostinger shared hosting. It captures the exact folder layout, commands, and pitfalls that were already resolved during the live deployment of `outdooreventspro.co.in`.

Use this document as the source of truth for future redeploys.

## Current Live Setup

### Hostinger Account

- SSH host: `145.79.210.26`
- SSH port: `65002`
- SSH user: `u862687956`

### Live Domain

- Main domain: `https://outdooreventspro.co.in`

### Live Application Paths

- Laravel app path: `/home/u862687956/crm-app`
- Domain public root: `/home/u862687956/domains/outdooreventspro.co.in/public_html`

### Live Database

- DB host: `localhost`
- DB port: `3306`
- DB name: `u862687956_Internal_crm`
- DB user: `u862687956_crm`

## Hostinger Constraints We Already Confirmed

- `npm` is not available on this Hostinger server.
- Frontend assets must be built locally and uploaded.
- `php artisan storage:link` does not work because `symlink()` is disabled.
- Static assets only work when the live `public_html/index.php` is the Laravel one with the correct absolute paths.
- The domain really serves from `/home/u862687956/domains/outdooreventspro.co.in/public_html`.

## Safe Deployment Model

- Keep the full Laravel project in `/home/u862687956/crm-app`
- Keep only public web files in `/home/u862687956/domains/outdooreventspro.co.in/public_html`
- Build assets locally, not on Hostinger
- Use `php artisan migrate --force` on live
- Do not use `migrate:fresh` on live
- Do not rely on symbolic links for public storage on this host

## Local Pre-Deploy Checklist

Run locally on Windows before every deploy:

```powershell
cd "C:\Users\SK\Desktop\Interior CRM"
composer install
npm install
npm run build
php artisan test
```

Only deploy after local tests pass.

## Production `.env` Requirements

Inside `/home/u862687956/crm-app/.env`, these values must exist and be correct:

```env
APP_NAME="Interior CRM"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://outdooreventspro.co.in

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u862687956_Internal_crm
DB_USERNAME=u862687956_crm
DB_PASSWORD=REAL_DATABASE_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

Important:

- The `.env` file must include an `APP_KEY=` line.
- If the line is missing, `php artisan key:generate --force` will fail.
- If `APP_KEY` changes on a live site, clear file sessions and browser cookies before testing.

## One-Time Server Bootstrap

SSH in:

```bash
ssh -p 65002 u862687956@145.79.210.26
```

Clone the app:

```bash
cd ~
rm -rf crm-app
git clone https://github.com/DakshIOT/Internal-Company-CRM.git crm-app
cd crm-app
composer install --no-dev --optimize-autoloader
```

Create and edit the environment file:

```bash
cp .env.example .env
nano .env
```

If `APP_KEY` is blank, generate it:

```bash
php artisan key:generate --force
```

Run production migrations and caches:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set writable permissions if needed:

```bash
chmod -R 775 storage bootstrap/cache
```

## Asset Build and Upload Workflow

Because `npm` is not available on Hostinger, build locally and upload the build output.

### Build Locally

```powershell
cd "C:\Users\SK\Desktop\Interior CRM"
npm install
npm run build
```

### Upload to the App Folder

```powershell
scp -P 65002 -r "C:\Users\SK\Desktop\Interior CRM\public\build" u862687956@145.79.210.26:/home/u862687956/crm-app/public/
```

### Sync the Same Build to the Live Public Root

Run on the server:

```bash
rm -rf /home/u862687956/domains/outdooreventspro.co.in/public_html/build
cp -r /home/u862687956/crm-app/public/build /home/u862687956/domains/outdooreventspro.co.in/public_html/
```

The app folder and the live public folder must use the same `build/manifest.json`.

## Live Public Root Sync

Copy Laravel public files into the domain root:

```bash
rm -rf /home/u862687956/domains/outdooreventspro.co.in/public_html/*
cp -r /home/u862687956/crm-app/public/* /home/u862687956/domains/outdooreventspro.co.in/public_html/
```

## Required Live `index.php`

File:

```text
/home/u862687956/domains/outdooreventspro.co.in/public_html/index.php
```

Contents:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = '/home/u862687956/crm-app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require '/home/u862687956/crm-app/vendor/autoload.php';

$app = require_once '/home/u862687956/crm-app/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

Do not leave the Hostinger default `index.php` in place. That caused a broken live deployment previously.

## Required Live `.htaccess`

File:

```text
/home/u862687956/domains/outdooreventspro.co.in/public_html/.htaccess
```

Contents:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Public Storage Workaround

Do not rely on:

```bash
php artisan storage:link
```

That fails on this host because `symlink()` is disabled.

Use this copy-based fallback instead:

```bash
mkdir -p /home/u862687956/domains/outdooreventspro.co.in/public_html/storage
cp -r /home/u862687956/crm-app/storage/app/public/* /home/u862687956/domains/outdooreventspro.co.in/public_html/storage/ 2>/dev/null
```

If attachments change later, recopy the storage files again.

## Routine Update SOP

Use this when local code has changed and needs to go live again.

### 1. Update Local Code

```powershell
cd "C:\Users\SK\Desktop\Interior CRM"
git pull
composer install
npm install
npm run build
php artisan test
```

### 2. Push Latest Code

```powershell
git add .
git commit -m "Describe the change"
git push origin main
```

### 3. Upload New Build Folder

```powershell
scp -P 65002 -r "C:\Users\SK\Desktop\Interior CRM\public\build" u862687956@145.79.210.26:/home/u862687956/crm-app/public/
```

### 4. SSH and Update Server Code

```bash
ssh -p 65002 u862687956@145.79.210.26
cd ~/crm-app
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Recopy Live Public Files

```bash
rm -rf /home/u862687956/domains/outdooreventspro.co.in/public_html/*
cp -r /home/u862687956/crm-app/public/* /home/u862687956/domains/outdooreventspro.co.in/public_html/
rm -rf /home/u862687956/domains/outdooreventspro.co.in/public_html/build
cp -r /home/u862687956/crm-app/public/build /home/u862687956/domains/outdooreventspro.co.in/public_html/
mkdir -p /home/u862687956/domains/outdooreventspro.co.in/public_html/storage
cp -r /home/u862687956/crm-app/storage/app/public/* /home/u862687956/domains/outdooreventspro.co.in/public_html/storage/ 2>/dev/null
```

### 6. Recheck the Live Entry Files

Make sure these still exist and are correct:

- `/home/u862687956/domains/outdooreventspro.co.in/public_html/index.php`
- `/home/u862687956/domains/outdooreventspro.co.in/public_html/.htaccess`

If the wrong `index.php` comes back, the site will break again.

## Verification Commands

Run on the server after deployment:

```bash
curl -I https://outdooreventspro.co.in/build/manifest.json
curl -I https://outdooreventspro.co.in/build/assets/app-8VAeUbk6.css
curl -s https://outdooreventspro.co.in/login | grep build
```

The exact asset hash may change after each local build. Use the current `manifest.json`.

## Browser Checks

Verify these in the browser:

- `https://outdooreventspro.co.in/login`
- `https://outdooreventspro.co.in/build/manifest.json`
- one current CSS file from the manifest
- one current JS file from the manifest

Then confirm:

- login page is styled
- admin login works
- employee login works
- venue selection works
- admin dashboard opens
- reports and exports work
- attachments preview or download correctly

## Failure Modes Already Hit

### 1. Missing `APP_KEY`

Symptom:

- `500 Server Error`
- log says `No application encryption key has been specified`

Fix:

- ensure `.env` contains `APP_KEY=`
- run `php artisan key:generate --force`
- rebuild caches

### 2. Stale Session or Cookie After `APP_KEY` Change

Symptom:

- login or dashboard keeps failing unexpectedly after key changes

Fix:

```bash
find ~/crm-app/storage/framework/sessions -type f -delete
php artisan optimize:clear
```

Then clear browser cookies or use Incognito.

### 3. CSS Missing While HTML Loads

Symptom:

- page loads as unstyled HTML

Fixes:

- verify `public_html/build/manifest.json` exists
- verify `public_html/build/assets/...` exists
- confirm the same build exists in `~/crm-app/public/build`
- replace the default Hostinger `index.php` with the Laravel one

### 4. Static Assets Returning `404`

Symptom:

- CSS or JS file from `/build/assets/...` returns `404`

Fixes:

- recopy the full build directory
- confirm docroot is `/home/u862687956/domains/outdooreventspro.co.in/public_html`
- fix permissions to `755` for folders and `644` for files if needed

### 5. `storage:link` Fails

Symptom:

- `Call to undefined function Illuminate\Filesystem\symlink()`

Fix:

- use the copy-based storage fallback in this SOP

## Rollback Advice

Before each live deployment:

- export the production database
- keep a copy of the previous `public_html`
- keep the previous commit available in Git

If a deployment fails:

- restore previous `public_html`
- restore previous `index.php`
- restore previous DB backup only if schema changes were applied and need rollback
