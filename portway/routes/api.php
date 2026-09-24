<?php

use App\Http\Controllers\Api\V1\BackupApiController;
use App\Http\Controllers\Api\V1\DatabaseApiController;
use App\Http\Controllers\Api\V1\DeploymentApiController;
use App\Http\Controllers\Api\V1\DomainApiController;
use App\Http\Controllers\Api\V1\SiteApiController;
use Illuminate\Support\Facades\Route;

/*
 |----------------------------------------------------------------------
 | Portway REST API (v1)
 |----------------------------------------------------------------------
 | Authenticated with Laravel Sanctum personal access tokens created
 | from Security > API Tokens. Every token carries its own abilities
 | (e.g. "sites:read", "sites:write") and an optional expiration —
 | see App\Livewire\Security\SecurityCenter for issuing them.
 */
// Rate limiting ("api" limiter, AppServiceProvider) is already applied to
// the whole api group by throttleApi() in bootstrap/app.php.
Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('sites', SiteApiController::class);
    Route::apiResource('domains', DomainApiController::class);
    Route::apiResource('databases', DatabaseApiController::class);
    Route::apiResource('backups', BackupApiController::class)->only(['index', 'show', 'store', 'destroy']);
    Route::apiResource('deployments', DeploymentApiController::class)->only(['index', 'show', 'store']);
});
