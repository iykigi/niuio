<?php

use App\Jobs\CheckAllPendingDnsJob;
use App\Jobs\RecalculateResourceUsageJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Portway platform scheduler --------------------------------------------

Schedule::job(new CheckAllPendingDnsJob)->everyFiveMinutes()->name('portway:check-dns')->withoutOverlapping();

Schedule::command('portway:renew-ssl')->daily()->at('02:00')->name('portway:renew-ssl')->withoutOverlapping();

Schedule::command('portway:run-cron-jobs')->everyMinute()->name('portway:run-cron-jobs')->withoutOverlapping();

Schedule::job(new RecalculateResourceUsageJob)->hourly()->name('portway:resource-usage')->withoutOverlapping();

Schedule::command('backup:cleanup-expired')->daily()->at('03:00')->name('portway:cleanup-backups');

Schedule::command('portway:collect-server-metrics')->everyMinute()->name('portway:server-metrics');
