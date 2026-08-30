# Ampere-Project

منصة أمبير الذكية لإدارة مولدات الكهرباء والخدمات الإنسانية — Laravel 12 (API) + Vue 3 (SPA).

## Figma Design
https://www.figma.com/design/rRx8ZKjI89rlZsT95sYmtQ/Graduation-Project?node-id=0-1

## Database Design
[Click here to view the Database Design (ERD & Schema)](https://github.com/aysha-al-shorafa/Ampere-Project/tree/main/Backend/database-design)

---

## Requirements

- PHP `^8.2` with the `bcmath` extension
- Composer
- Node.js + npm
- MySQL
- (Optional, for real-time features) a running [Reverb](https://reverb.laravel.com/) server — see [Real-time / broadcasting](#real-time--broadcasting) below

## Setup (local development)

```bash
git clone <repo-url>
cd Ampare-management2027/Backend

composer setup   # composer install, copies .env.example -> .env, generates APP_KEY, runs migrations, npm install + build
```

`composer setup` runs the individual steps below for you; run them manually only if you need to redo one:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Then edit `.env` for your local database:

```env
DB_CONNECTION=mysql
DB_DATABASE=ampare_management
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate        # add --seed to also load demo data
npm install
```

## Running the app

```bash
composer dev
```

This starts, together, in one terminal: the PHP dev server (`php artisan serve`), the queue worker (`php artisan queue:listen`), log tailing (`php artisan pail`), the Vite dev server (`npm run dev`), and the Reverb broadcasting server (`php artisan reverb:start --debug`).

To run any of these individually instead:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
php artisan reverb:start
```

## Running tests

```bash
composer test   # clears cached config, then runs the full PHPUnit/Feature suite
npm run test    # Vitest — frontend composable/component tests
```

## Verifying your setup

```bash
php artisan app:check-production        # sanity-checks production-sensitive .env flags (safe to run locally too)
php artisan broadcasting:check-config   # confirms the Reverb/broadcasting config is internally consistent
curl http://localhost:8000/up           # health check — verifies the app booted AND that the database, queue, and (if configured) Reverb are actually reachable; returns 500 with details if any dependency is down
```

## Real-time / broadcasting

This app uses [Laravel Reverb](https://reverb.laravel.com/) (`BROADCAST_CONNECTION=reverb`) for real-time features (e.g. live notifications). If Reverb isn't running, features that depend on it may not behave as expected — including some server-side actions that broadcast an event as part of completing a request (see `REVERB-approve-coupling` in `docs/audit/FULL_PROJECT_AUDIT_REPORT.md` for a known related issue). Start it with `php artisan reverb:start` (already included in `composer dev`), and use `/up` above to confirm it's reachable.

## Deployment notes

- Run `php artisan app:check-production` as part of your deploy pipeline before switching traffic to a new release — it fails loudly if production-unsafe settings (e.g. `APP_DEBUG=true`, an insecure session cookie) are detected. It is intentionally **not** wired into this repo's push/PR CI (see `DEVOPS-CI-check` in the audit report), since CI runs against `.env.example`'s dev-oriented defaults.
- `compose.yaml` provides a Docker-based local/staging environment (`./vendor/bin/sail up -d`). Container boot has not been verified in every environment — see `DEVOPS-003-boot` in the audit report.
- Set real production values for `REVERB_*`, `FLARE_KEY` (error tracking — see `DEVOPS-004-verify`), and mail/queue drivers before going live; `.env.example`'s defaults are for local development only.

## Full audit trail

Every hardening/remediation finding, fix, and its verification evidence for this project is tracked in [`docs/audit/FULL_PROJECT_AUDIT_REPORT.md`](docs/audit/FULL_PROJECT_AUDIT_REPORT.md) — the single source of truth for what's been fixed, what's confirmed-but-not-fixed, and what's still open.
