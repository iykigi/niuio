<?php

use App\Models\Site;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('site.{siteId}.provisioning', function ($user, $siteId) {
    $site = Site::find($siteId);

    return $site && ($site->user_id === $user->id || $user->can('sites.view'));
});

Broadcast::channel('site.{siteId}.deployment', function ($user, $siteId) {
    $site = Site::find($siteId);

    return $site && ($site->user_id === $user->id || $user->can('sites.manage'));
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
