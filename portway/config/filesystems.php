<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         |----------------------------------------------------------------
         | Hosting disk
         |----------------------------------------------------------------
         | The root under which every website's isolated directory lives:
         | storage/hosting_data/{account_id}/{site_slug}/. The File
         | Manager, deployments and backups are all scoped underneath it.
         | On a real multi-node install this root lives on the hosting
         | node itself and the panel reaches it over SFTP (see the "ssh"
         | provisioner driver), not through this disk directly.
         */
        'hosting' => [
            'driver' => 'local',
            'root' => env('PORTWAY_HOSTING_ROOT', storage_path('hosting_data')),
            'throw' => true,
            'visibility' => 'private',
            'permissions' => [
                'file' => ['public' => 0644, 'private' => 0640],
                'dir' => ['public' => 0755, 'private' => 0750],
            ],
        ],

        'backups' => [
            'driver' => 'local',
            'root' => env('PORTWAY_BACKUPS_ROOT', storage_path('app/backups')),
            'throw' => true,
        ],

        /*
         |----------------------------------------------------------------
         | Releases disk
         |----------------------------------------------------------------
         | Desktop application builds (Windows/macOS/Linux installers)
         | uploaded for distribution. Deliberately private: downloads are
         | always served through App\Http\Controllers\Releases\
         | ReleaseDownloadController, which checks the build is actually
         | live (or that the requester owns it) before streaming a byte.
         | Nothing here is ever reachable by guessing a URL.
         */
        'releases' => [
            'driver' => 'local',
            'root' => env('PORTWAY_RELEASES_ROOT', storage_path('app/releases')),
            'throw' => true,
            'visibility' => 'private',
            'permissions' => [
                'file' => ['public' => 0644, 'private' => 0640],
                'dir' => ['public' => 0755, 'private' => 0750],
            ],
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
