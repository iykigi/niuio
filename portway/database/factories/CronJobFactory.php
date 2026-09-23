<?php

namespace Database\Factories;

use App\Models\CronJob;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CronJob>
 */
class CronJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'label' => 'Clear expired sessions',
            'command' => 'php artisan session:gc',
            'preset' => 'daily',
            'schedule' => CronJob::PRESETS['daily'],
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CronJob $cronJob) {
            if (! $cronJob->user_id) {
                $cronJob->user_id = Site::find($cronJob->site_id)?->user_id;
            }
        });
    }
}
