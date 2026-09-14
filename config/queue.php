<?php
return [
    'default'     => env('QUEUE_CONNECTION', 'sync'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => ['driver' => 'database', 'connection' => env('DB_QUEUE_CONNECTION'), 'table' => env('DB_QUEUE_TABLE', 'jobs'), 'queue' => env('DB_QUEUE', 'default'), 'retry_after' => (int) env('QUEUE_RETRY_AFTER', 900), 'after_commit' => true],
        'redis' => ['driver' => 'redis', 'connection' => env('QUEUE_REDIS_CONNECTION','default'), 'queue' => env('REDIS_QUEUE','media-default'), 'retry_after' => (int) env('QUEUE_RETRY_AFTER', 900), 'block_for' => 5, 'after_commit' => true],
    ],
    'failed'      => ['driver' => 'database-uuids', 'database' => env('DB_CONNECTION', 'sqlite'), 'table' => 'failed_jobs'],
];
