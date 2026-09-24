<?php

return [

    /*
     |--------------------------------------------------------------------
     | Provisioning driver
     |--------------------------------------------------------------------
     | Every action that touches real server infrastructure (creating a
     | site's directory, writing a vhost, issuing an SSL cert, creating a
     | database, checking DNS) goes through App\Services\Provisioning\
     | ProvisionerDriver. "local" is a fully functional, filesystem-backed
     | implementation used for development and evaluation — every
     | control-panel feature works against it out of the box. Point this
     | at "ssh" once real hosting nodes are registered in the admin panel.
     */
    'provisioner' => env('PORTWAY_PROVISIONER', 'local'),

    /*
     |--------------------------------------------------------------------
     | Super Admin account
     |--------------------------------------------------------------------
     | Created by `php artisan db:seed` (and `php artisan portway:install`,
     | which `php artisan serve` runs on first start). Change the password
     | before the panel is reachable by anyone else.
     */
    'super_admin' => [
        'email' => env('PORTWAY_SUPER_ADMIN_EMAIL', 'admin@portway.test'),
        'password' => env('PORTWAY_SUPER_ADMIN_PASSWORD', 'password'),
    ],

    /*
     |--------------------------------------------------------------------
     | Default account limits
     |--------------------------------------------------------------------
     | These are the values a brand-new user account is created with.
     | Super Admins can override them per-account (Admin > Users) or
     | change these platform-wide defaults (Admin > Settings) — see the
     | `system_settings` table, which takes priority over this file at
     | runtime through App\Services\Settings.
     */
    'defaults' => [
        'storage_quota_mb' => (int) env('PORTWAY_DEFAULT_STORAGE_QUOTA_MB', 10240),
        'max_websites' => (int) env('PORTWAY_DEFAULT_MAX_WEBSITES', 10),
        'max_databases' => (int) env('PORTWAY_DEFAULT_MAX_DATABASES', 10),
        'max_domains' => (int) env('PORTWAY_DEFAULT_MAX_DOMAINS', 20),
        'max_subdomains_per_site' => (int) env('PORTWAY_DEFAULT_MAX_SUBDOMAINS', 10),
        'max_cron_jobs' => (int) env('PORTWAY_DEFAULT_MAX_CRON_JOBS', 20),
        'max_backups' => (int) env('PORTWAY_DEFAULT_MAX_BACKUPS', 5),
        'max_email_accounts' => (int) env('PORTWAY_DEFAULT_MAX_EMAIL_ACCOUNTS', 10),
        'bandwidth_quota_mb' => (int) env('PORTWAY_DEFAULT_BANDWIDTH_QUOTA_MB', 51200),
        'max_php_processes' => (int) env('PORTWAY_DEFAULT_MAX_PHP_PROCESSES', 20),
        'max_db_connections' => (int) env('PORTWAY_DEFAULT_MAX_DB_CONNECTIONS', 10),
        'cpu_limit_percent' => (int) env('PORTWAY_DEFAULT_CPU_LIMIT_PERCENT', 100),
        'memory_limit_mb' => (int) env('PORTWAY_DEFAULT_MEMORY_LIMIT_MB', 768),
    ],

    /*
     |--------------------------------------------------------------------
     | Storage quota categories
     |--------------------------------------------------------------------
     | Whether each category counts toward the account's overall storage
     | quota. Website files always count; the rest are admin-configurable
     | (see StorageUsageCalculator).
     */
    'quota_categories' => [
        'websites' => true,
        'databases' => true,
        'email' => true,
        'backups' => (bool) env('PORTWAY_BACKUPS_COUNT_TOWARD_QUOTA', false),
    ],

    'storage_warning_thresholds' => [80, 90, 95, 100],

    /*
     |--------------------------------------------------------------------
     | Server / DNS
     |--------------------------------------------------------------------
     */
    'server_ip' => env('PORTWAY_SERVER_IP', '203.0.113.10'),
    'temporary_domain_suffix' => env('PORTWAY_TEMP_DOMAIN_SUFFIX', 'portway.site'),

    /*
     |--------------------------------------------------------------------
     | Website project types
     |--------------------------------------------------------------------
     | Drives the "create website" wizard. Each type maps to a
     | provisioning recipe in App\Services\Provisioning\Recipes.
     */
    'project_types' => [
        'php_empty' => ['label' => 'Empty PHP Website', 'runtime' => 'php', 'icon' => 'code-bracket'],
        'static_html' => ['label' => 'Static HTML Website', 'runtime' => 'static', 'icon' => 'document-text'],
        'laravel' => ['label' => 'Laravel', 'runtime' => 'php', 'icon' => 'bolt'],
        'wordpress' => ['label' => 'WordPress', 'runtime' => 'php', 'icon' => 'globe-alt'],
        'nodejs' => ['label' => 'Node.js', 'runtime' => 'node', 'icon' => 'cube'],
        'react' => ['label' => 'React', 'runtime' => 'node', 'icon' => 'squares-2x2'],
        'vue' => ['label' => 'Vue', 'runtime' => 'node', 'icon' => 'puzzle-piece'],
        'nextjs' => ['label' => 'Next.js', 'runtime' => 'node', 'icon' => 'forward'],
        'nuxt' => ['label' => 'Nuxt', 'runtime' => 'node', 'icon' => 'forward'],
        'vite' => ['label' => 'Vite', 'runtime' => 'node', 'icon' => 'bolt'],
        'upload' => ['label' => 'Upload Existing Website', 'runtime' => 'php', 'icon' => 'arrow-up-tray'],
        'git' => ['label' => 'Import from Git', 'runtime' => 'php', 'icon' => 'code-bracket-square'],
    ],

    /*
     |--------------------------------------------------------------------
     | PHP versions
     |--------------------------------------------------------------------
     | The set an admin has made available on the fleet. Disabling a
     | version here hides it from the website wizard and PHP Manager,
     | it does not uninstall it from nodes.
     */
    'php_versions' => array_filter(explode(',', env('PORTWAY_PHP_VERSIONS', '8.1,8.2,8.3,8.4'))),
    'default_php_version' => env('PORTWAY_DEFAULT_PHP_VERSION', '8.3'),

    'node_versions' => array_filter(explode(',', env('PORTWAY_NODE_VERSIONS', '18,20,22'))),
    'default_node_version' => env('PORTWAY_DEFAULT_NODE_VERSION', '20'),

    /*
     |--------------------------------------------------------------------
     | Backups
     |--------------------------------------------------------------------
     */
    'backups' => [
        'max_per_site' => (int) env('PORTWAY_MAX_BACKUPS_PER_SITE', 5),
        'retention_days' => (int) env('PORTWAY_BACKUP_RETENTION_DAYS', 30),
        'max_size_mb' => (int) env('PORTWAY_MAX_BACKUP_SIZE_MB', 5120),
        'default_frequency' => env('PORTWAY_DEFAULT_BACKUP_FREQUENCY', 'manual'),
    ],

    /*
     |--------------------------------------------------------------------
     | Application releases
     |--------------------------------------------------------------------
     | Desktop builds users distribute from their Portway account. Exactly
     | one build per platform is ever live; publishing a new one archives
     | the build it replaces, and a platform with nothing published simply
     | does not appear on the public download page.
     */
    'releases' => [
        'max_size_mb' => (int) env('PORTWAY_MAX_RELEASE_SIZE_MB', 2048),
        'max_per_application' => (int) env('PORTWAY_MAX_RELEASES_PER_APPLICATION', 50),
        'max_applications_per_user' => (int) env('PORTWAY_MAX_APPLICATIONS_PER_USER', 10),
    ],

    /*
     |--------------------------------------------------------------------
     | Feature toggles
     |--------------------------------------------------------------------
     */
    'features' => [
        'email_hosting' => (bool) env('PORTWAY_EMAIL_HOSTING_ENABLED', false),
        'malware_scanning' => (bool) env('PORTWAY_MALWARE_SCANNING_ENABLED', false),
        'social_login' => (bool) env('PORTWAY_SOCIAL_LOGIN_ENABLED', false),
        'registration_open' => (bool) env('PORTWAY_REGISTRATION_OPEN', true),
        'app_distribution' => (bool) env('PORTWAY_APP_DISTRIBUTION_ENABLED', true),
    ],

];
