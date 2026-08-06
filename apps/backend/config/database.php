<?php

use Illuminate\Support\Str;

$redisPassword = filled(env('REDIS_PASSWORD')) ? env('REDIS_PASSWORD') : null;
$redisBase = [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'username' => env('REDIS_USERNAME'),
    'password' => $redisPassword,
    'port' => env('REDIS_PORT', '6379'),
    'max_retries' => env('REDIS_MAX_RETRIES', 3),
    'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
    'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
    'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
];

return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'blogflix'),
            'username' => env('DB_USERNAME', 'blogflix'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'blogflix')).'-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'default' => [...$redisBase, 'database' => env('REDIS_DB', '0')],
        'cache' => [...$redisBase, 'database' => env('REDIS_CACHE_DB', '1')],
        'session' => [...$redisBase, 'database' => env('REDIS_SESSION_DB', '2')],
        'queue' => [...$redisBase, 'database' => env('REDIS_QUEUE_DB', '3')],
    ],
];
