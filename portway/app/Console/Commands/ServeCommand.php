<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

/**
 * `php artisan serve`, plus a first-run setup: on a fresh checkout it
 * runs `php artisan portway:install` (.env, app key, SQLite database,
 * migrations, Super Admin) before starting the server, so the panel
 * works on the very first `serve`. Nothing extra happens once installed,
 * and it never runs automatically in production.
 */
#[AsCommand(name: 'serve')]
class ServeCommand extends BaseServeCommand
{
    public function handle()
    {
        // No .env at all means a fresh checkout (APP_ENV then falls back to
        // "production", so that check alone would skip the setup).
        $freshCheckout = ! file_exists($this->laravel->environmentFilePath());

        if (($freshCheckout || ! $this->laravel->isProduction()) && ! InstallPortway::isInstalled()) {
            $this->components->info('First run — setting Portway up (php artisan portway:install)…');

            // A separate process, so the install sees the .env it creates.
            $install = new Process([PHP_BINARY, 'artisan', 'portway:install', '--ansi'], base_path());
            $install->setTimeout(null);
            $install->run(fn ($type, $buffer) => $this->output->write($buffer));

            if (! $install->isSuccessful()) {
                $this->components->warn('Setup did not finish — the server is starting anyway so you can see the error page.');
            }
        }

        return parent::handle();
    }
}
