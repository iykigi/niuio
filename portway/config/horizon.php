<?php

return [

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'admin/horizon'),

    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'portway_horizon:'),

    'middleware' => ['web', 'auth', 'role:Super Admin|Admin'],

    'waits' => [
        'redis:default' => 60,
        'redis:provisioning' => 120,
        'redis:ssl' => 120,
        'redis:backups' => 300,
        'redis:deployments' => 120,
        'redis:metrics' => 60,
        'redis:notifications' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => ['job' => 24, 'queue' => 24],
    ],

    'fast_termination' => false,

    'memory_limit' => 256,

    'defaults' => [
        'supervisor-interactive' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications'],
            'balance' => 'auto',
            'maxProcesses' => 4,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-provisioning' => [
            'connection' => 'redis',
            'queue' => ['provisioning', 'ssl', 'deployments'],
            'balance' => 'auto',
            'maxProcesses' => 4,
            'tries' => 2,
            'timeout' => 600,
        ],
        'supervisor-maintenance' => [
            'connection' => 'redis',
            'queue' => ['backups', 'metrics'],
            'balance' => 'simple',
            'maxProcesses' => 2,
            'tries' => 2,
            'timeout' => 1800,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-interactive' => ['maxProcesses' => 6],
            'supervisor-provisioning' => ['maxProcesses' => 6],
            'supervisor-maintenance' => ['maxProcesses' => 3],
        ],

        'local' => [
            'supervisor-interactive' => ['maxProcesses' => 2],
            'supervisor-provisioning' => ['maxProcesses' => 2],
            'supervisor-maintenance' => ['maxProcesses' => 1],
        ],
    ],

];
