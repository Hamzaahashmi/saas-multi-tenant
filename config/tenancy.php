<?php

return [
    'tenant_model'  => \App\Models\Tenant::class,
    'id_generator'  => Stancl\Tenancy\UUIDGenerator::class,

    // Your main app domain (not a subdomain)
    'central_domains' => [
        env('CENTRAL_DOMAIN', 'localhost'),
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // CacheTenancyBootstrapper, FilesystemTenancyBootstrapper, QueueTenancyBootstrapper
        // are not needed for this API-only application
    ],

    'database' => [
        'based_on'    => 'mysql',           // uses your default DB connection
        'prefix'      => env('TENANCY_DB_PREFIX', 'tenant_'),
        'suffix'      => '',
        'template_tenant_connection' => 'mysql',
        'central_connection'         => 'mysql',
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql'  => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'  => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path'  => [database_path('migrations')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
        '--force' => true,
    ],

    // Feature flags
    'features' => [
        Stancl\Tenancy\Features\TenantConfig::class,
    ],

    'home_url' => '/',
    'exempt_domains' => [],

    'filesystem' => [
        'suffix_base'   => 'tenant',
        'disks'         => ['local', 'public'],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'override_tag' => '%tenant%',
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'routes' => false, // We handle routes manually
    'queue_passthrough_default_connection' => false,
];
