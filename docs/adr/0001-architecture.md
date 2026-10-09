# ADR 0001 — Fleet Tracker Architecture

Date: 2026-10-09 · Status: accepted

## Context

Port of a single-file Node/SQLite tracker (`fleet-tracker/server.js`) to Laravel 13
(PHP 8.3+, Livewire). Must run with zero infra (SQLite file), support a MySQL
upgrade path, keep the vehicle lifecycle + transition permissions + audit +
notifications + 8 reports + sale-order/delivery-note PDFs, and ship Arabic-first
bilingual UI.

## Decision: modular monolith

- `app/Domain/Reporting/` holds pure report queries (`ReportQuery` contract,
  `ReportRegistry::map()`), PDF (`ReportPdfService`) and Excel (`GenericReportExport`)
  exporters — no HTTP/Livewire dependencies, unit-testable with sqlite `:memory:`.
- `app/Livewire/*` + `app/Http/Controllers/*` are thin adapters over the domain.
- No microservices, no separate API: Blade + Livewire server-rendered UI, one
  deployable (`app` + `web` nginx containers).

### Vehicle transitions

State machine: `purchased → shipped → arrived → clearing → cleared → instock → sold → delivered`.
Each transition declares `roles` (who may perform) + `n` (roles to notify);
`admin` bypasses all checks. Every transition writes an audit event
(who/when/what) and a notification row (in-app; email fan-out to active users
of the target role when SMTP is configured). Permissions are data-driven so
Admin Settings can edit them without code changes.

## Why SQLite now, MySQL path later

- **Now:** `DB_CONNECTION=sqlite`, file `database/database.sqlite` (WAL-capable,
  zero-config, single-file backup, matches legacy `fleet-v2.db`). Queue/cache/
  session use the `database` driver — no Redis required to boot. Docker persists
  it in the `sqlite-data` volume.
- **Later:** `config/database.php` already defines `mysql`; `docker-compose.yml`
  has an optional `db: mysql:8` service behind `--profile mysql`. Migration is
  env-only (`DB_CONNECTION=mysql`, `DB_HOST=db`, …). No code change: Eloquent
  + migrations are driver-agnostic; SQLite-specific SQL is banned in queries.
- Redis (`redis:7` service) is available for cache/queue scale-up but not required.

## Why Blade + Livewire (not SPA/API)

- Team velocity: server-rendered forms/tables match the legacy server-rendered UI;
  Livewire gives reactivity (`ReportIndex`/`ReportShow`) without building/maintaining
  a JS SPA or versioned API.
- Bilingual/RTL is simpler in Blade (`__('…')`, `dir="rtl"`, PDF fonts) than
  duplicating i18n in a frontend bundle.
- If a mobile/API client appears, add `routes/api.php` over the same domain
  services — no rewrite.

## Bilingual (ar first, en fallback)

- `APP_LOCALE=ar`, `APP_FALLBACK_LOCALE=en`: Arabic RTL by default, English fallback
  for missing keys. `APP_FAKER_LOCALE=ar_SA` for seeds.
- Rule: no hardcoded user strings in Blade/Livewire/PDF — everything via
  `lang/{ar,en}/*.php`. PDFs embed an Arabic-capable font (dompdf).

## Consequences

- Keep domain free of framework HTTP; cover transitions + all 8 reports with
  Feature/Unit tests (sqlite `:memory:`).
- Gates: Pint (`laravel` preset) + PHPStan level 6 + PHPUnit in CI.
- Docker default (`compose up`) must boot without MySQL; MySQL profile is for
  staging/prod evaluation.
