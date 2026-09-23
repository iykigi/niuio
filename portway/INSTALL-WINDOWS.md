# Installing Portway on Windows

This is the whole installation, start to finish, written for a Windows
machine running a local Apache/PHP/MySQL stack (XAMPP, Laragon, WAMP, or
similar with an `htdocs` folder). Follow it in order — every step here
exists because skipping it produces a specific, confusing error.

## Before you start: pick ONE folder

The single most common way this goes wrong is having two copies of the
project: one you run `composer install` in, and a different one your web
server actually serves. `composer install` then succeeds in a folder nobody
is looking at, and the browser keeps showing:

```
Failed opening required '...\public/../vendor/autoload.php'
```

Decide now which folder is the real one. If your web server serves
`C:\xampp\htdocs\portway`, then that is the folder — do everything below
inside it, and delete or ignore any copy on your Desktop or elsewhere.

Check which folder your server serves by opening its config (`httpd.conf`,
or your stack's control panel) and looking at the `DocumentRoot` /
virtual-host path. Portway must be served from the project's `public`
subfolder, e.g. `DocumentRoot "C:/xampp/htdocs/portway/public"`.

## 1. Requirements

- **PHP 8.2 or newer** with these extensions enabled in `php.ini`:
  `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`,
  `bcmath`, `fileinfo`, `curl`, `zip`, `gd`.
  Check with `php -m`. Enable one by removing the `;` in front of its
  `extension=` line in `php.ini`, then restart Apache.
- **Composer 2** — `composer --version`
- **Node.js 20+ and npm** — `node -v`
- **MySQL or MariaDB** (your stack almost certainly includes it)

Two extensions Laravel Horizon asks for — `pcntl` and `posix` — are
POSIX-only and **do not exist on Windows at all**. This is not something you
can install or fix; step 3 tells Composer to ignore them. Horizon's queue
worker will not run on Windows either; use `php artisan queue:work` instead
(step 7).

## 2. Put the files in place and configure

From the project folder (the one your server serves):

```powershell
cd C:\xampp\htdocs\portway          # <- your actual folder
copy .env.example .env
```

Open `.env` in a text editor. For the simplest first run that needs no
MySQL and no Redis, set:

```
APP_URL=http://localhost/portway/public
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
SESSION_SECURE_COOKIE=false
```

and **delete** the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and
`DB_PASSWORD` lines. Then create the empty database file:

```powershell
New-Item -ItemType File database\database.sqlite -Force
```

`SESSION_SECURE_COOKIE=false` matters: Portway defaults secure cookies to on
(correct for production over HTTPS), and leaving it on over plain `http://`
on localhost means you can never stay logged in.

For a real deployment instead, keep `DB_CONNECTION=mysql`, create a database
and user in MySQL, and fill in the `DB_*` values.

## 3. Install the PHP dependencies

```powershell
Remove-Item vendor -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item composer.lock -Force -ErrorAction SilentlyContinue
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

Notes on why each part is there:

- Deleting `composer.lock` first matters if the folder ever held a different
  Laravel project. A stale lock file pins versions that conflict with this
  project's requirements, and the errors it produces name packages you never
  asked for.
- `--ignore-platform-req` is **singular** and takes a value, and has to be
  repeated once per extension. `--ignore-platform-reqs` (plural) is a
  different, valueless flag.
- Newer Composer versions refuse by default to install any package with a
  recorded security advisory, which currently blocks the whole Laravel 11
  range. `composer.json` therefore sets
  `config.policy.advisories.block` to `false`. Run `composer audit` after
  installing to see the advisories yourself and decide what you want to do
  about them before putting this on a public server.

Let this run to the end — it prints `Generating optimized autoload files`
when it finishes. Then confirm the file the web server was looking for now
exists:

```powershell
Test-Path vendor\autoload.php      # must print True
```

If it prints `False`, the install did not complete. Scroll up in the same
terminal window and read the first error, not the last one.

## 4. Install the front-end and build the assets

```powershell
npm install
npm run build
```

Without `npm run build` the pages load with no styling at all — Laravel
cannot find the compiled CSS/JS and throws a Vite manifest error.

## 5. Create the application key and the database tables

```powershell
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

`--seed` creates the roles, permissions and your Super Admin account:

```
email:    admin@portway.test
password: password
```

Change that password the moment you log in — and change
`PORTWAY_SUPER_ADMIN_PASSWORD` in `.env` before seeding if this machine is
reachable by anyone else.

## 6. Raise PHP's upload limits (only if you will publish app builds)

The app-distribution feature accepts installers up to 2 GB by default, but
PHP's own limits override that. In `php.ini`:

```
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 600
memory_limit = 512M
```

Restart Apache afterwards. Lower these to match the largest build you
actually expect — they are a resource limit, not a target.

## 7. Run it

If you are serving through Apache, visit your configured URL. Otherwise
Laravel's own server works fine:

```powershell
php artisan serve
```

and open `http://127.0.0.1:8000`.

Queued work (site provisioning, backups, SSL renewal) needs a worker. On
Windows, in a second terminal:

```powershell
php artisan queue:work
```

and in a third, for scheduled jobs:

```powershell
php artisan schedule:work
```

Do not use `php artisan horizon` on Windows — it needs the `pcntl`
extension that Windows does not have.

## Troubleshooting the errors you are most likely to hit

**`Failed opening required '...\vendor\autoload.php'`**
`composer install` has not successfully completed *in the folder the web
server is serving*. Go back to step 3, in that exact folder.

**`Class "Laravel\Sanctum\Sanctum" not found`** (or any other vendor class)
Same cause: dependencies are not installed. `composer show laravel/sanctum`
tells you whether the package is really there.

**`Could not find package dragonmage/cron-expression`**
An old copy of `composer.json` with a typo'd package name. The correct name
is `dragonmantank/cron-expression` — make sure the `composer.json` in this
folder is the current one.

**`The "--ignore-platform-reqs" option does not accept a value`**
You used the plural form. Use `--ignore-platform-req=ext-pcntl` (singular),
once per extension.

**`requires ext-pcntl` / `requires ext-posix`**
Expected on Windows. Add the matching `--ignore-platform-req` flag as in
step 3.

**A blank page, or "Vite manifest not found"**
`npm run build` has not been run (step 4).

**Logged out immediately after logging in, over plain http**
`SESSION_SECURE_COOKIE=false` is missing from `.env` (step 2).

**`SQLSTATE[HY000] [1045] Access denied` on migrate**
The `DB_USERNAME`/`DB_PASSWORD` in `.env` do not match a real MySQL user, or
the database named in `DB_DATABASE` does not exist yet. Create it in
phpMyAdmin first.

**Anything else**
`storage/logs/laravel.log` holds the real error with a stack trace. The
first few lines of the most recent entry are what matters.
