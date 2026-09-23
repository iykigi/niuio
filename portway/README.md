# Portway

Portway is a free, modern web hosting platform and control panel — an original,
from-scratch alternative to hPanel/cPanel-style products, built on Laravel 11 +
Livewire 3 + Tailwind CSS. Every account gets free hosting (10 GB storage by
default), with no billing, subscriptions, or payment flow anywhere in the
product. Administrators can change the default limits per account or
platform-wide from the admin panel.

This repository is **hand-written, unexecuted application code** — it was
produced in a sandboxed environment with no access to Packagist, npm, or a
database/queue/broadcast server, so `composer install`, `npm install`, and the
test suite have never actually been run. Section 6 below explains exactly what
that means for you before you deploy this.

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

- PHP 8.3+ with the usual Laravel extensions (`pdo_mysql` or `pdo_sqlite`,
  `redis` if you use the Redis drivers below, `zip`, `gd` or `imagick`)
- Composer 2
- Node.js 20+ and npm
- MySQL/MariaDB (or SQLite for a quick local trial)
- Redis (default session/cache/queue/broadcast driver — see below to avoid it)

## Getting started

**On Windows, read [INSTALL-WINDOWS.md](INSTALL-WINDOWS.md) instead** — there
are three Windows-specific gotchas (two PHP extensions that cannot exist on
Windows at all, Composer's security-advisory blocking, and serving the app
from the right directory) that will otherwise stop `composer install` before
it ever creates `vendor/`.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

- For a **quick local trial without Redis or MySQL**, set
  `DB_CONNECTION=sqlite` (remove the other `DB_*` lines — Portway creates
  `database/database.sqlite` on demand), and set `SESSION_DRIVER=array`,
  `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=log`.
- For a **real deployment**, configure `DB_*` for MySQL/MariaDB and point
  `REDIS_*` at a Redis instance (used for sessions, cache, queues, and — via
  Reverb/Pusher-protocol broadcasting — the live site-creation progress bar).

Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build          # or `npm run dev` while developing
```

The seeders create the RBAC roles/permissions and a Super Admin account:

```
email:    admin@portway.test          (override with PORTWAY_SUPER_ADMIN_EMAIL)
password: password                    (override with PORTWAY_SUPER_ADMIN_PASSWORD)
```

**Change that password immediately in a real deployment.**

Run the app:

```bash
php artisan serve
php artisan horizon        # queue worker with dashboard (or: php artisan queue:work)
php artisan schedule:work  # cron jobs, SSL renewal, DNS checks, metrics collection
```

If you're using the `local` provisioner (the default), everything above is
enough — every feature works with no other servers. To host real websites on
real Linux nodes, add at least one node from **Admin → Servers** and set
`PORTWAY_PROVISIONER=ssh`.

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

## Important: what has and hasn't been verified

This codebase was written in a sandboxed environment with outbound access to
Packagist and npm blocked, so **`composer install`, `npm install`,
`php artisan migrate`, and the test suite were never actually executed**
here. Every file was hand-written to be syntactically and semantically
correct (all PHP files pass `php -l`, and every class/route/view reference
was manually cross-checked against the files that define it), but "compiles
and cross-references correctly" is not the same guarantee as "a real test
run passed." Before relying on this in production:

1. Run `composer install && npm install` and fix any dependency-version
   surprises (versions were pinned from memory of each package's API, not
   from a live `composer show`).
2. Run `php artisan migrate --seed` against a real database and watch for
   any migration ordering issue.
3. Run `composer test` and fix anything a real Laravel/Pest runtime catches
   that static review couldn't (subtle Eloquent behavior, Livewire lifecycle
   edge cases, and exact third-party API method names/signatures — e.g.
   `pragmarx/google2fa`, `bacon/bacon-qr-code` — are the most likely spots).
4. Review `SshProvisionerDriver` and the expected `portway-agent` contract
   carefully before pointing it at a real server — it has not been
   integration-tested against a live node.

Everything else — the data model, the authorization boundaries, the quota
system, the admin panel, and the UI — should be complete and usable as
delivered.
