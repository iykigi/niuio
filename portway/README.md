# Portway

Portway is a free, modern web hosting platform and control panel — an original,
from-scratch alternative to hPanel/cPanel-style products, built on Laravel 11 +
Livewire 3 + Tailwind CSS. Every account gets free hosting (10 GB storage by
default), with no billing, subscriptions, or payment flow anywhere in the
product. Administrators can change the default limits per account or
platform-wide from the admin panel.

## Quick start

```bash
composer install
php artisan serve
```

Open http://127.0.0.1:8000 and log in as **admin@portway.test** / **password**.

That's all: on its first start `php artisan serve` creates `.env`, the app
key and a SQLite database, runs the migrations and seeds the Super Admin
(the same thing `php artisan portway:install` does). It needs no MySQL,
Redis or Node.js — the compiled CSS/JS ships in `public/build`.

### دەستپێکی خێرا (کوردی)

```bash
composer install
php artisan serve
```

پاشان بڕۆ بۆ http://127.0.0.1:8000 و بە **admin@portway.test** و وشەی
نهێنی **password** بچۆ ژوورەوە. یەکەم جار کە `php artisan serve` کار
دەکات، خۆی فایلی `.env`، کلیلی ئەپ و داتابەیسی SQLite دروست دەکات و
هەموو خشتەکان و هەژماری Super Admin ئامادە دەکات — پێویست بە MySQL،
Redis یان Node.js ناکات. لە Windows، پێش `composer install` سەیری
[INSTALL-WINDOWS.md](INSTALL-WINDOWS.md) بکە (چالاککردنی `pdo_sqlite`،
`fileinfo` و `zip` لە `php.ini`).

## What's included

- **Hosting**: create/manage websites (PHP or Node.js, or import from Git),
  connect a domain from any registrar, real MySQL/MariaDB databases with a
  built-in phpMyAdmin-style manager, a browser file manager with a Monaco code
  editor, free auto-renewing SSL, DNS management.
- **Developer tools**: a sandboxed web terminal, Git-based deployments with
  build logs, cron jobs, per-site environment variables, redirects.
- **Reliability**: on-demand backups with one-click restore, storage/bandwidth
  usage dashboards, resource usage history.
- **Security**: two-factor authentication (TOTP + recovery codes), a
  tamper-resistant hash-chained activity log, per-account and platform-wide IP
  blocking, file-integrity change tracking, command-injection and
  path-traversal defenses on every user-supplied path/command.
- **App distribution**: publish desktop builds of your software for Windows,
  macOS and Linux and get a public download page. Exactly one build per
  platform is ever live; publishing a new one archives the build it replaces,
  and a platform with nothing published is hidden from the page entirely
  rather than shown as unavailable. Uploading and publishing are separate
  steps — a build is uploaded with its full metadata first and only goes live
  on an explicit publish. Owners can roll back to an archived build (which
  archives the current one) and deleting is non-destructive: the build moves
  to an admin-only archive where staff can restore it to its owner or purge
  it for good. Every download is streamed through an authorization check, so
  drafts and archived builds are never reachable by URL.
- **Admin panel**: platform overview, user management (suspend, quotas, role
  changes, impersonation), hosting node management with live health metrics,
  runtime-editable system settings, announcements, a support inbox, and a
  full RBAC editor (Super Admin / Admin / Support / Moderator / User, plus
  custom roles with a granular permission matrix), and the release archive
  of builds their owners deleted.
- **API**: a versioned REST API (`/api/v1/...`) authenticated with Sanctum
  personal access tokens scoped to specific abilities (`sites:read`,
  `deployments:write`, etc.), manageable from Account Settings.
- **Marketing site & onboarding**: a landing page, a features page, and a
  short onboarding flow for brand-new accounts.

## Architecture

Every action that touches real infrastructure — writing a vhost, issuing an
SSL cert, running a shell command, creating a database — goes through a single
`App\Services\Provisioning\ProvisionerDriver` interface. Two implementations
ship:

- **`local`** (default): a fully functional filesystem + SQLite-backed driver.
  Every feature in the panel genuinely works against it with no external
  servers — this is what you want for local development and for evaluating
  the product.
- **`ssh`**: connects to real Linux hosting nodes over SSH — real
  `certbot`/`mysql`/systemd commands, expects a small `portway-agent` helper
  script on each node for templated multi-step actions. Register nodes from
  **Admin → Servers**, then set `PORTWAY_PROVISIONER=ssh` in `.env`.

This is what lets Portway's control plane, hosting nodes, database
infrastructure, and backup storage scale independently, and lets you add more
hosting nodes later without changing any application code — only the driver
and the data.

Other structural notes:

- **Authorization**: every model has a Policy (`app/Policies`), and nothing
  relies on the frontend hiding a button — every Livewire action and API
  route re-checks authorization server-side. Super Admin bypasses every check
  via a single `Gate::before` in `AppServiceProvider`.
- **Security allowlisting**: `CommandSanitizer` allowlists the binaries a
  hosting account may invoke (php, composer, node, npm, git, wp-cli, common
  file utilities) and rejects privilege escalation, command substitution, and
  traversal — used by the web terminal, cron jobs, and Git deploy commands.
  `PathResolver` does the equivalent for the file manager, scoping every
  operation under `{user_id}/{site_slug}` on the `hosting` disk.
- **Storage quotas**: `StorageUsageCalculator` sums usage across websites,
  databases, email, and (optionally) backups, with 80/90/95/100% warning
  thresholds and a broadcast event when a threshold is crossed.
- **Settings**: `config/portway.php` sets the shipped defaults;
  `App\Services\Settings` layers admin-edited `system_settings` rows on top
  at runtime, no deploy required.

## Requirements

- PHP 8.2+ with `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `openssl`,
  `fileinfo`, `zip`, `curl`, `xml` and `gd`. `php -m` lists what's enabled.
- Composer 2
- Only for a real deployment: MySQL/MariaDB and Redis
- Only if you change the CSS/JS: Node.js 20.19+ and npm

## Getting started

**On Windows, read [INSTALL-WINDOWS.md](INSTALL-WINDOWS.md) first** — it
covers enabling the right PHP extensions in `php.ini`.

```bash
composer install
php artisan serve        # first start also runs `php artisan portway:install`
```

(`composer setup` does the same preparation without starting the server.)

The Super Admin created on first start:

```
email:    admin@portway.test          (override with PORTWAY_SUPER_ADMIN_EMAIL)
password: password                    (override with PORTWAY_SUPER_ADMIN_PASSWORD)
```

**Change that password immediately in a real deployment.**

### What the default `.env` gives you

`.env.example` is set up for a single machine with no other services:

| Setting | Default | Notes |
| --- | --- | --- |
| `DB_CONNECTION` | `sqlite` | `database/database.sqlite`, created for you |
| `SESSION_DRIVER` / `CACHE_STORE` | `file` | |
| `QUEUE_CONNECTION` | `sync` | provisioning, backups, SSL and deploys run immediately — no worker needed |
| `BROADCAST_CONNECTION` | `log` | the site-creation progress screen polls instead |
| `MAIL_MAILER` | `log` | verification and password-reset links are written to `storage/logs/laravel.log` |
| `PORTWAY_PROVISIONER` | `local` | websites, databases (SQLite files) and backups live under `storage/` |

Scheduled work (user cron jobs, SSL renewal, DNS checks, metrics) runs from
`php artisan schedule:work` in a second terminal.

### A real deployment

Configure `DB_*` for MySQL/MariaDB, point `REDIS_*` at Redis and set
`SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`,
`BROADCAST_CONNECTION=reverb`, `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`,
`APP_DEBUG=false`, then:

```bash
composer install --no-dev --optimize-autoloader
php artisan portway:install
php artisan horizon           # queue workers for every Portway queue
php artisan schedule:work     # or a cron entry running `php artisan schedule:run` every minute
php artisan reverb:start      # live progress over WebSockets
```

Without Horizon, a plain worker must listen on all of Portway's queues:
`php artisan queue:work --queue=provisioning,ssl,backups,deployments,metrics,notifications,default`.

The `local` provisioner runs terminal/cron/deploy commands on the panel's
own machine (behind the command allowlist) — it is meant for development
and evaluation. To host real websites for other people, add Linux nodes
from **Admin → Servers** and set `PORTWAY_PROVISIONER=ssh`.

## Running the tests

```bash
composer test
# or directly:
./vendor/bin/pest
```

The suite (`tests/Unit`, `tests/Feature`) covers the security-critical paths
from phases 1–4: the command allowlist and path resolver in isolation;
registration, login, 2FA enrollment/challenge/recovery codes; RBAC and the
Super Admin bypass; website/backup/cron quota enforcement and cross-account
isolation; file-manager path-traversal defenses; suspension and admin
authorization boundaries; and the support ticket flow. It runs against an
in-memory SQLite database with faked storage disks, so it needs no external
services.

## Roadmap (phases 5–7)

This delivery is phases 1–4 (data model, provisioning abstraction, RBAC, the
full user-facing hosting panel) plus the admin panel, landing page/onboarding,
and this test suite. Not yet built:

- **Email hosting module** (`EmailAccount` model and migration already exist)
  — mailbox creation, webmail, and an SMTP/IMAP-facing provisioner driver
  method set.
- **Redirects & subdomains UI** — the `Redirect` and subdomain-type `Domain`
  rows are modeled and API-ready but have no dedicated panel screens yet.
- **Real-time everywhere** — site creation progress already broadcasts over
  Reverb; the web terminal and deployment log viewer currently poll/refetch
  rather than streaming over a socket.
- **`ssh` driver hardening** — the interface and command surface are final,
  but the node-side `portway-agent` helper script itself (templated vhost
  configs, certbot wrapper, MySQL grants) still needs to be written and
  packaged for a real Linux node.
- **Malware/file-integrity scanning job** — `FileChangeEvent` and the
  `malware_scanning_enabled` feature flag exist; the background scanner that
  populates them does not yet.
- **i18n** — the locale picker in Account Settings is wired up, but only
  English strings exist so far.
- **Billing** — intentionally out of scope forever: Portway has no pricing or
  payment code anywhere by design.

## What has been verified

The panel has been installed from a clean checkout and exercised end to
end in a real browser against `php artisan serve` (SQLite, `local`
provisioner, `sync` queue): registration with e-mail verification,
password reset, 2FA enrolment and login, every page of the user panel and
the admin panel, creating/editing/deleting websites, domains, DNS records,
databases (browse, edit rows, SQL, import/export), files (editor, upload,
zip), backups and restores, cron jobs, the web terminal, Git connect and
deploy, app build upload/publish/download/archive, support tickets,
announcements, roles, suspension and impersonation, and every `/api/v1`
endpoint with a Sanctum token. `composer test` passes.

Not exercised against real infrastructure: the `ssh` provisioner driver
and its node-side `portway-agent` contract, MySQL/MariaDB as the panel
database, Redis/Horizon queues and Reverb broadcasting. Review
`SshProvisionerDriver` carefully before pointing it at a real server.
