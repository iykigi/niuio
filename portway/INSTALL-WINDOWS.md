# Installing Portway on Windows

Works with a plain PHP install or any local stack (XAMPP, Laragon, WAMP…).
You do **not** need MySQL, Redis or Node.js to run it.

## 1. Enable the PHP extensions

Find the `php.ini` your command line uses:

```powershell
php --ini
```

Open it and make sure these lines exist **without** a `;` in front
(XAMPP ships several of them commented out):

```
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

Save the file. `php -m` should now list `pdo_sqlite`, `fileinfo` and `zip`.

You don't need `pcntl` or `posix` — they don't exist on Windows, and the
project is already configured so Composer doesn't ask for them.

## 2. Install and run

In the project folder (the one that contains `artisan`):

```powershell
composer install
php artisan serve
```

The first `php artisan serve` sets everything up by itself — it creates
`.env`, the application key and `database\database.sqlite`, creates the
tables and the Super Admin — and then starts the server. Open
http://127.0.0.1:8000 and log in:

```
email:    admin@portway.test
password: password
```

Change that password (Security → Password) before anyone else can reach
the machine.

## Optional

- **Scheduled jobs** (user cron jobs, SSL renewal, DNS checks): run
  `php artisan schedule:work` in a second terminal.
- **Apache instead of `artisan serve`**: point the virtual host's
  `DocumentRoot` at the project's `public` folder, e.g.
  `C:/xampp/htdocs/portway/public`, and set `APP_URL` in `.env` to match.
- **MySQL instead of SQLite**: create an empty database, set
  `DB_CONNECTION=mysql` and the `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/
  `DB_PASSWORD` lines in `.env`, then run `php artisan portway:install`.
- **Changing the CSS/JS**: install Node.js 20.19+ and run `npm install`
  then `npm run build`. Not needed otherwise — the built files are included.
- **Large app builds**: the app-distribution feature is capped by PHP's
  own upload limits. For 2 GB installers set `upload_max_filesize = 2048M`,
  `post_max_size = 2048M` and `max_execution_time = 600` in `php.ini`.

## Troubleshooting

**`Failed opening required '...\vendor\autoload.php'`**
`composer install` hasn't finished in *this* folder. Run it again here and
read the first error it prints.

**`could not find driver` / `pdo_sqlite extension is not enabled`**
Step 1 — `extension=pdo_sqlite` is still commented out in the `php.ini`
that `php --ini` shows.

**`requires ext-fileinfo` / `ext-zip` during `composer install`**
Step 1 — enable that extension.

**Logged out right after logging in**
You changed `SESSION_SECURE_COOKIE` to `true` while using plain
`http://`. Set it back to `false` in `.env`.

**Anything else**
`storage\logs\laravel.log` has the full error. `php artisan portway:install`
can be run again at any time — it only does what is still missing.
