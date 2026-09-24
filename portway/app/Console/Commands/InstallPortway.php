<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Takes a fresh checkout to a working panel: .env, application key,
 * SQLite file, migrations, seed data (roles + Super Admin) and the
 * public storage link. Every step is skipped when it's already done,
 * so it is safe to run again at any time — `php artisan serve` runs it
 * automatically on a local machine (see ServeCommand).
 */
class InstallPortway extends Command
{
    protected $signature = 'portway:install';

    protected $description = 'Prepare Portway to run: .env, app key, database, migrations, seed data and storage link (safe to re-run).';

    public function handle(): int
    {
        $envPath = $this->laravel->environmentFilePath();

        if (! file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
            $this->components->info('Created .env from .env.example.');
        }

        if (empty(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        if (config('database.default') === 'sqlite' && ! extension_loaded('pdo_sqlite')) {
            $this->components->error('PHP\'s pdo_sqlite extension is not enabled.');
            $this->line('  Enable it in php.ini (remove the ";" in front of "extension=pdo_sqlite"), restart, and run this again —');
            $this->line('  or point DB_CONNECTION in .env at MySQL/MariaDB instead. (php --ini shows which php.ini is used.)');

            return self::FAILURE;
        }

        $this->ensureSqliteDatabaseExists();

        try {
            $this->call('migrate', ['--force' => true]);

            if (! User::query()->exists()) {
                $this->call('db:seed', ['--force' => true]);
            }
        } catch (Throwable $e) {
            $this->components->error('Could not set up the database: '.$e->getMessage());
            $this->line('  Check the DB_* settings in .env (the default, DB_CONNECTION=sqlite, needs no database server).');

            return self::FAILURE;
        }

        if (! file_exists(public_path('storage'))) {
            try {
                $this->callSilently('storage:link');
            } catch (Throwable) {
                // Only needed for publicly served uploads; never block setup on it.
            }
        }

        if (! file_exists(public_path('build/manifest.json')) && ! file_exists(public_path('hot'))) {
            $this->components->warn('Front-end assets are missing — run `npm install && npm run build`.');
        }

        $this->newLine();
        $this->components->info('Portway is ready.');
        $this->components->twoColumnDetail('Log in as', (string) config('portway.super_admin.email'));
        $this->components->twoColumnDetail('Password', (string) config('portway.super_admin.password'));
        $this->newLine();

        return self::SUCCESS;
    }

    private function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');

        if ($path && $path !== ':memory:' && ! file_exists($path)) {
            @mkdir(dirname($path), 0755, true);
            touch($path);
            $this->components->info('Created the SQLite database at '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
        }
    }

    /**
     * True when there is nothing for this command to do. Cheap enough to
     * call before every `php artisan serve`.
     */
    public static function isInstalled(): bool
    {
        if (! file_exists(app()->environmentFilePath()) || empty(config('app.key'))) {
            return false;
        }

        try {
            if (! Schema::hasTable('migrations') || ! User::query()->exists()) {
                return false;
            }

            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);

            return array_diff(array_keys($files), $migrator->getRepository()->getRan()) === [];
        } catch (Throwable) {
            return false;
        }
    }
}
