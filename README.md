# Fleet Tracker — Laravel 13 (PHP 8.3+, SQLite default, Livewire)

Modular-monolith fleet tracker: vehicle lifecycle (purchase → shipment → arrival → customs → stock → sale → delivery),
reporting (PDF/Excel), bilingual UI (Arabic-first, English fallback).

## Quick start (local)

```bash
composer setup          # install, copy .env, key:generate, migrate, npm build
# or step by step:
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan serve        # http://localhost:8000
```

Legacy import (Node/SQLite `fleet-tracker` app):

```bash
php artisan fleet:import-legacy --path=../fleet-tracker/data/fleet-v2.db
php artisan migrate --force
```

Queue (database driver by default, no Redis required):

```bash
php artisan queue:work --tries=3 --sleep=3
php artisan schedule:work   # cron replacement in dev
```

## Quality gates

```bash
vendor/bin/pint --test              # code style (laravel preset)
vendor/bin/phpstan analyse          # Larastan, level 6
php artisan test                    # PHPUnit (sqlite :memory:)
```

CI (`.github/workflows/ci.yml`) runs all three on push/PR with PHP 8.3.

## Docker

```bash
docker compose up --build            # app (php-fpm) + web (nginx :8000) + redis + worker + scheduler
docker compose --profile mysql up    # also start MySQL 8 (optional path)
```

- Default boot is **SQLite** (`database/database.sqlite` via `sqlite-data` volume). MySQL is opt-in.
- Web: `http://localhost:8000` → `web` (nginx) → `app:9000` (php-fpm, root `/var/www/html/public`).
- Worker runs `queue:work`, scheduler runs `schedule:work`.

Switch to MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=fleet
DB_USERNAME=fleet
DB_PASSWORD=secret
```

## Roles matrix

| Role | Can do |
|---|---|
| `admin` (System Administrator) | Everything: users, audit log, settings, delete vehicles, all transitions |
| `purchaser` | Register purchase/shipment (`purchase`, `ship`), create vehicles, attach files |
| `tracker` | `ship`, confirm arrival (`arrive`) |
| `customs` | Start/complete clearance (`clear1`, `clear2`) |
| `warehouse` | Receive into stock (`receive`), deliver (`deliver`) |
| `sales` | Issue sale order (`sell`) |
| `viewer` | Read-only (reports, vehicles, no transitions, no uploads) |

Every transition is logged (who/when/what) and notifies the target role (in-app + email when SMTP is set).
Per-action `roles` / notify lists are configurable in Admin Settings.

## Reports (`/reports`, `ReportRegistry`)

| Key | Report |
|---|---|
| `summary` | Summary (counts by stage) |
| `stock` | Stock (in-stock vehicles) |
| `available` | Available for sale |
| `sales` | Sales by buyer |
| `shipments` | Shipments (B/L tracking) |
| `customs` | Customs clearance |
| `full` | Full fleet dump |
| `quality` | Data quality check (future dates, missing B/L, missing buyer, …) |

Exports: PDF (dompdf) via `ReportPdfService`, Excel via `GenericReportExport` (maatwebsite/excel).
Documents: sale order PDF + delivery note PDF per vehicle (`DocumentController`).

## Bilingual note

- Default locale `APP_LOCALE=ar` (RTL), fallback `APP_FALLBACK_LOCALE=en`.
- All user-facing strings go through `__('…')` / `lang/ar|en/*.php`. Reports/PDFs use an Arabic-capable font.
- Faker locale `APP_FAKER_LOCALE=ar_SA` for realistic seed data.

## Project layout

- `app/Domain/Reporting/` — report queries + registry + PDF/Excel exporters (pure domain, no HTTP).
- `app/Livewire/Reports/` + `app/Http/Controllers/` — thin UI/adapters over the domain.
- `app/Models/` — Eloquent models. `database/` — SQLite file (git-ignored).
- `docs/adr/0001-architecture.md` — why modular monolith / SQLite-now-MySQL-later / Blade+Livewire / bilingual.
