<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'default_storage_quota_mb' => ['value' => config('portway.defaults.storage_quota_mb'), 'group' => 'quotas'],
            'default_max_websites' => ['value' => config('portway.defaults.max_websites'), 'group' => 'quotas'],
            'default_max_databases' => ['value' => config('portway.defaults.max_databases'), 'group' => 'quotas'],
            'backups_count_toward_quota' => ['value' => config('portway.quota_categories.backups'), 'group' => 'quotas'],
            'available_php_versions' => ['value' => config('portway.php_versions'), 'group' => 'runtimes'],
            'available_node_versions' => ['value' => config('portway.node_versions'), 'group' => 'runtimes'],
            'registration_open' => ['value' => config('portway.features.registration_open'), 'group' => 'features'],
            'email_hosting_enabled' => ['value' => config('portway.features.email_hosting'), 'group' => 'features'],
            'malware_scanning_enabled' => ['value' => config('portway.features.malware_scanning'), 'group' => 'features'],
            'server_ip' => ['value' => config('portway.server_ip'), 'group' => 'network'],
            'temporary_domain_suffix' => ['value' => config('portway.temporary_domain_suffix'), 'group' => 'network'],
        ];

        foreach ($defaults as $key => $data) {
            SystemSetting::query()->firstOrCreate(['key' => $key], [
                'value' => $data['value'],
                'group' => $data['group'],
                'type' => is_bool($data['value']) ? 'boolean' : (is_array($data['value']) ? 'array' : 'string'),
                'is_public' => false,
            ]);
        }
    }
}
