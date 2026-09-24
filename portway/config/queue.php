<?php

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => 300,
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
     |--------------------------------------------------------------------
     | Portway queues
     |--------------------------------------------------------------------
     | Long-running provisioning work is split across dedicated queues so
     | a burst of, say, backup jobs never starves interactive site
     | creation. Horizon (config/horizon.php) supervises all of them.
     */
    'names' => [
        'provisioning' => 'provisioning',
        'ssl' => 'ssl',
        'backups' => 'backups',
        'deployments' => 'deployments',
        'metrics' => 'metrics',
        'notifications' => 'notifications',
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
