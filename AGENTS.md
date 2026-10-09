# AGENTS.md — fleet-tracker

Laravel 13 / PHP 8.3+ / Livewire 4 / Tailwind 4 + Vite. SQLite by default, MySQL opt-in. Arabic-first RTL UI.

## Setup & run

```bash
composer setup          # install + .env + key + migrate + npm build
# manual: composer install; cp .env.example .env; php artisan key:generate
#         touch database/database.sqlite; php artisan migrate --force
php artisan serve       # :8000
npm run dev             # Vite HMR (inputs: resources/css/app.css, resources/js/app.js)
php artisan queue:work --tries=3 --sleep=3
php artisan schedule:work   # dev cron replacement
php artisan fleet:import-legacy --path=../fleet-tracker/data/fleet-v2.db
```

- Default DB is SQLite file `database/database.sqlite` (git-ignored); queue/cache/session use `database` driver — no Redis needed to boot.
- MySQL is opt-in only: `docker compose --profile mysql up` + `DB_CONNECTION=mysql DB_HOST=db …` (`DB_DATABASE=fleet DB_USERNAME=fleet DB_PASSWORD=secret`).
- Docker default: `docker compose up --build` (app php-fpm + web nginx `:8000` + worker + scheduler + redis). MySQL NOT started unless profile is passed.

## Verify (CI: `.github/workflows/ci.yml`, PHP 8.3)

```bash
vendor/bin/pint --test              # laravel preset, required
vendor/bin/phpstan analyse          # Larastan level 6, paths: app config bootstrap/app.php
php artisan test                    # PHPUnit, sqlite :memory: (see phpunit.xml)
php artisan test --filter=TransitionTest          # single class
php artisan test tests/Feature/Vehicle/TransitionTest.php  # single file
```

CI order: `pint -> phpstan -> php artisan test --parallel`.

## Architecture

- Modular monolith, no API/SPA: `routes/web.php` only (Blade views) + Livewire reactivity. `bootstrap/app.php` registers only `SetLocale` web middleware, health `/up`.
- `app/Domain/` is the real logic — keep it free of HTTP/Livewire:
  - `Domain/Vehicle/`: `Models/Vehicle.php`, `Enums/{VehicleStage,UserRole}`, `Transitions/*` + `Support/TransitionRegistry.php`, `Actions/*`, `Policies/VehiclePolicy.php`, `Observers/`, `Listeners/SendTransitionNotifications.php`.
  - `Domain/Reporting/`: `ReportRegistry::map()` (8 keys: `summary stock available sales shipments customs full quality`) + `Queries/*` implementing `ReportQuery` + `Services/ReportPdfService.php` (dompdf) + `Exports/GenericReportExport.php` (maatwebsite/excel).
  - `Domain/Identity/Services/SettingsService.php`: company/currency + per-transition `perm` overrides, cached forever under `fleet.settings`.
- Thin adapters: `app/Livewire/*` (Vehicles, Reports, Admin, Dashboard, Auth) + `app/Http/Controllers/{Report,Document,AttachmentDownload}Controller.php`.
- Wiring: `app/Providers/DomainServiceProvider.php` (TransitionRegistry singleton, VehiclePolicy gate, VehicleObserver).

## Conventions & gotchas

- **Vehicle PK is `vin` (string, non-incrementing, SoftDeletes).** Routes use `/{vin}` with `->where('vin', '.*')` — keep that constraint. `getRouteKeyName()` returns `vin`.
- **Transitions:** keys `ship arrive clear1 clear2 receive sell deliver`; state flow `purchased→shipped→arrived→clearing→cleared→instock→sold→delivered`. Each declares `from()/to()/allowedRoles()/notifyRoles()`; `admin` bypasses all checks. Every transition writes audit (`VehicleActivity`) + notification (in-app; email only when SMTP set). Roles/notify are editable in Admin Settings — don't hardcode them elsewhere.
- **Legacy compat:** `Vehicle::LEGACY_MAP` (`pdate voy dest ship arr cust_no cust_date loc in_date ord_no client …`) — `AbstractTransition::normalizeKeys()` accepts short keys on write; keep the map in sync if columns change. No SQLite-specific SQL — queries must stay driver-agnostic (ADR 0001).
- **Bilingual:** default `APP_LOCALE=ar` (RTL), fallback `en`, faker `ar_SA`. No hardcoded user strings in Blade/Livewire/PDF — always `__('…')` + `lang/{ar,en}/*.php` (+ `ar.json`/`en.json`). PDFs need Arabic-capable font.
- Env quirk: PHPUnit forces `DB_CONNECTION=sqlite DB_DATABASE=:memory: QUEUE_CONNECTION=sync`; don't rely on local `.env` DB in tests.
- See `docs/adr/0001-architecture.md` for why SQLite-now/MySQL-later + Blade+Livewire.
