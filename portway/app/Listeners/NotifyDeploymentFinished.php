<?php

namespace App\Listeners;

use App\Events\DeploymentFinished;
use App\Notifications\HostingActivityNotification;

class NotifyDeploymentFinished
{
    public function handle(DeploymentFinished $event): void
    {
        $deployment = $event->deployment;
        $site = $deployment->site;

        if ($deployment->status === 'failed') {
            $site->user->notify(new HostingActivityNotification(
                'Website deployment failed',
                "The deployment for {$site->name} failed. Check the deployment log for details.",
                'danger',
                "/sites/{$site->id}/git",
                alsoEmail: true,
            ));
        } else {
            $site->user->notify(new HostingActivityNotification(
                'Deployment succeeded',
                "{$site->name} was deployed successfully.",
                'success',
                "/sites/{$site->id}/git",
            ));
        }
    }
}
