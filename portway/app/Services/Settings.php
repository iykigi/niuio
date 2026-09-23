<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The runtime-editable layer on top of config/portway.php: values an
 * admin changes from Admin > Settings live in the `system_settings`
 * table and take priority over the config file's value, without a
 * deploy. Anything not overridden here still falls back to config().
 */
class Settings
{
    private const CACHE_KEY = 'portway:system_settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, ?User $updatedBy = null, string $group = 'general'): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => is_bool($value) ? 'boolean' : (is_array($value) ? 'array' : 'string'),
                'updated_by' => $updatedBy?->id,
            ]
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return SystemSetting::query()->pluck('value', 'key')->all();
        });
    }

    public function group(string $group): array
    {
        return SystemSetting::query()->where('group', $group)->pluck('value', 'key')->all();
    }
}
