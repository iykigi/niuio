<?php

namespace App\Events;

use App\Models\Site;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast on the site's private channel so the creation wizard's
 * progress screen updates live via Echo/Reverb. The wizard also polls
 * `sites.provisioning_progress`/`status_message` as a no-JS-required
 * fallback, so this event is an enhancement, not a hard dependency.
 */
class SiteProvisioningProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $siteId,
        public string $step,
        public int $percent,
        public bool $failed = false,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("site.{$this->siteId}.provisioning")];
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }
}
