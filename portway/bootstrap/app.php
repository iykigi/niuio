<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureIpIsNotBlocked;
use App\Http\Middleware\EnsureTwoFactorChallengeIsPassed;
use App\Http\Middleware\RecordLastSeenActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureIpIsNotBlocked::class,
            RecordLastSeenActivity::class,
        ]);

        $middleware->api(append: [
            EnsureIpIsNotBlocked::class,
        ]);

        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'two-factor' => EnsureTwoFactorChallengeIsPassed::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
