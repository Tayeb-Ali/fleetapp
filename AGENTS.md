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
- External docs: for versioned Laravel-ecosystem APIs prefer Boost `search-docs`; for other packages use `context7` MCP tools (`resolve-library-id` then `query-docs`).
- Project skills (`.agents/skills/`, single standard location): Foysal v1.1.2 (`laravel-patterns` before any new class under `app/Domain/` — Decision Gate decides Action/Service/Repository/Query/VO; `laravel-eloquent` for queries/migrations, `laravel-async` for jobs/events/cache, `laravel-rest-api` for the HTTP edge, `laravel-testing` for test strategy) + Boost (`livewire-development`, `tailwindcss-development`, `laravel-best-practices`, `testing-best-practices`).

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
