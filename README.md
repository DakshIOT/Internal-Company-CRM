# Internal Company CRM

![Laravel](https://img.shields.io/badge/Laravel-9.52-red)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![Livewire](https://img.shields.io/badge/Livewire-2.12-fb70a9)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38bdf8)
![MySQL](https://img.shields.io/badge/MySQL-8%2B-00758f)
![Status](https://img.shields.io/badge/Status-Internal%20Use%20Only-111827)

Laravel-based internal CRM for venue-scoped business operations. The application supports fixed employee roles, mandatory venue selection for employees, server-side totals, symbol-free money display, attachment handling, admin reports, and Excel exports.

## Highlights

- Fixed roles: `admin`, `employee_a`, `employee_b`, `employee_c`
- Employee flow: `Login -> Venue Selection -> Dashboard`
- Strict venue isolation across all employee-facing data
- Function Entry workflow with packages, extra charges, installments, discounts, and attachments
- Daily Income, Daily Billing, Vendor Entry, and Admin Income ledgers
- Admin reporting with explicit employee, venue, module, service, package, and vendor filters
- Excel exports with plain numeric output and no currency symbols
- Responsive Blade UI built for desktop and mobile

## Tech Stack

- Laravel 9
- Blade
- Livewire 2
- Alpine.js
- Tailwind CSS
- Vite
- MySQL / MariaDB
- `maatwebsite/excel`

## Business Rules

- Admin can access the global dashboard without selecting a venue.
- Employees must select an assigned venue before entering protected modules.
- Venue context is stored in session as `selected_venue_id`.
- Only `employee_b` has Vendor Entry access.
- Each venue has exactly 4 vendor slots.
- Money is stored in integer minor units.
- No currency symbol or currency code appears in the UI or exports.

## Core Modules

### Admin

- Admin dashboard
- Venues
- Employees
- Employee assignment workspace
- Services
- Packages
- Admin Income
- Reports and Excel exports

### Employee

- Dashboard
- Venue selection and switching
- Function Entry
- Daily Income
- Daily Billing
- Vendor Entry for `employee_b`

## Local Setup

1. Clone the repository:

```bash
git clone https://github.com/DakshIOT/Internal-Company-CRM.git
cd Internal-Company-CRM
```

2. Install dependencies:

```bash
composer install
npm install
```

3. Create the environment file:

```bash
cp .env.example .env
```

4. Configure `.env`:

```env
APP_NAME="Interior CRM"
APP_ENV=local
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=interior_crm
DB_USERNAME=root
DB_PASSWORD=
```

5. Generate the app key and seed the database:

```bash
php artisan key:generate
php artisan migrate:fresh --seed
```

6. Run the app:

```bash
npm run dev
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Demo Credentials

Password for all demo users:

```text
Password@123
```

Users:

- Admin: `admin@interiorcrm.local`
- Employee A: `employee.a@interiorcrm.local`
- Employee B: `employee.b@interiorcrm.local`
- Employee C: `employee.c@interiorcrm.local`

## Employee Access Notes

- `employee_a`: Function Entry, Daily Income, Daily Billing
- `employee_b`: Function Entry, Daily Income, Daily Billing, Vendor Entry
- `employee_c`: Function Entry only
- Frozen fund applies only to `employee_a`

## Testing

Run the full suite:

```bash
php artisan test
```

Build production assets:

```bash
npm run build
```

## Deployment

This project is designed to stay compatible with Hostinger Business shared hosting:

- no Redis requirement
- no Horizon
- no websocket dependency
- standard Laravel filesystem usage
- synchronous export support

Use PHP `8.2` on Hostinger where available.

## Repository Guidance

- Business source of truth: [`CRM_BRIEF.md`](./CRM_BRIEF.md)
- Implementation source of truth: [`PROJECT_PLAN.md`](./PROJECT_PLAN.md)
- Repo working rules: [`AGENTS.md`](./AGENTS.md)

## Notes

- This is an internal company CRM, not a multi-tenant SaaS.
- Role and venue rules are intentionally fixed in code.
- Server-side totals are authoritative.
