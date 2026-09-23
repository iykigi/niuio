<?php

namespace App\Services\Provisioning;

use App\Enums\SiteStatus;
use App\Exceptions\QuotaExceededException;
use App\Jobs\ProvisionSiteJob;
use App\Jobs\DeprovisionSiteJob;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SiteProvisioningService
{
    public function create(User $user, array $data): Site
    {
        $this->assertWithinLimits($user);

        return DB::transaction(function () use ($user, $data) {
            $projectType = $data['project_type'];
            $recipe = config("portway.project_types.{$projectType}") ?? config('portway.project_types.php_empty');

            $site = Site::create([
                'user_id' => $user->id,
                'server_id' => Server::pickForNewSite()?->id,
                'name' => $data['name'],
                'project_type' => $projectType,
                'runtime' => $recipe['runtime'],
                'php_version' => $recipe['runtime'] === 'php' ? ($data['php_version'] ?? config('portway.default_php_version')) : null,
                'node_version' => $recipe['runtime'] === 'node' ? ($data['node_version'] ?? config('portway.default_node_version')) : null,
                'status' => SiteStatus::Provisioning,
                'provisioning_progress' => 0,
            ]);

            if ($site->server) {
                $site->server->increment('current_sites');
            }

            ProvisionSiteJob::dispatch($site, $data)->onQueue(config('queue.names.provisioning'));

            return $site;
        });
    }

    public function delete(Site $site): void
    {
        $site->update(['status' => SiteStatus::Deleting]);
        DeprovisionSiteJob::dispatch($site)->onQueue(config('queue.names.provisioning'));
    }

    private function assertWithinLimits(User $user): void
    {
        if ($user->sites()->count() >= $user->max_websites) {
            throw QuotaExceededException::websiteLimit($user->max_websites);
        }

        if (app(\App\Services\Storage\StorageUsageCalculator::class)->isOverQuota($user)) {
            throw QuotaExceededException::storage();
        }
    }
}
