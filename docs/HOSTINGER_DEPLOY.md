# Hostinger Deployment Guide

This guide is for deploying the Internal Company CRM to Hostinger Business shared hosting.

## Hosting Assumptions

- PHP `8.2`
- MySQL or MariaDB database created in Hostinger hPanel
- SSH enabled
- Domain or subdomain already connected
- Laravel app stored above `public_html`

## Deployment Pack

- Production environment template: [`../.env.production.example`](../.env.production.example)
- Main project overview: [`../README.md`](../README.md)

## Recommended Server Layout

```text
/home/username/domains/yourdomain.com/
  crm-app/
    app/
    bootstrap/
    config/
    database/
    public/
    resources/
    routes/
    storage/
    vendor/
  public_html/
    index.php
    .htaccess
    build/
```

`crm-app` holds the Laravel application. `public_html` holds the public entrypoint and compiled assets.

## Step 1: Prepare Locally

From your local machine:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan test
```

Make sure all tests pass before deployment.

## Step 2: Upload the App

Upload the full Laravel project to a folder above `public_html`, for example:

```text
/home/username/domains/yourdomain.com/crm-app
```

Then copy the contents of the repo `public/` directory into `public_html`.

Also copy the generated `public/build` directory into `public_html/build`.

## Step 3: Create Production Environment

Create `.env` inside the uploaded app folder using [`../.env.production.example`](../.env.production.example).

Minimum fields to change:

- `APP_URL`
- `APP_KEY`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `MAIL_*` values if you will use mail

## Step 4: Update `public_html/index.php`

Point the public entry file to the real Laravel app path.

Example:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../crm-app/vendor/autoload.php';

$app = require_once __DIR__.'/../crm-app/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

If your uploaded folder name is different, replace `crm-app` with the real folder name.

## Step 5: Run Server Commands

SSH into the Hostinger account and run:

```bash
cd ~/domains/yourdomain.com/crm-app
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 6: Writable Directories

If needed:

```bash
chmod -R 775 storage bootstrap/cache
```

## Step 7: Asset Checklist

Confirm these exist in `public_html`:

- `index.php`
- `.htaccess`
- `build/manifest.json`
- `build/assets/...`

If assets are missing, the UI will load without styling or scripts.

## Step 8: Database Checklist

After migration, verify these areas:

- users exist
- venues exist
- venue assignments exist
- services and packages exist
- vendor slots exist for each venue
- sample/demo seed data exists if you used seeding in production

If you do not want demo data on production, use:

```bash
php artisan migrate --force
```

instead of:

```bash
php artisan migrate --seed --force
```

## Step 9: Go-Live Checklist

Check all of these before considering the site live:

- homepage opens
- login works
- employee venue selection works
- admin dashboard opens
- employee dashboard opens
- Function Entry creates and edits correctly
- Daily Income creates correctly
- Daily Billing creates correctly
- Vendor Entry works for `employee_b`
- attachments preview and download correctly
- reports filter correctly
- Excel exports download correctly
- no debug errors are visible
- `APP_DEBUG=false`

## Optional Scheduled Task

If a scheduler is needed later, add a cron job in hPanel using the Hostinger PHP path pattern:

```bash
/usr/bin/php /home/username/domains/yourdomain.com/crm-app/artisan schedule:run
```

Adjust the path to match the real deployment location.

## Rollback Advice

Before each deployment:

- keep a backup of the previous app files
- export the production database
- upload new code only after a passing local test/build run

If a deployment fails:

- restore the previous files
- restore the previous database backup if schema changes were applied
