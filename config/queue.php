<?php
return [
    'default'     => env('QUEUE_CONNECTION', 'sync'),
    'connections' => ['sync' => ['driver' => 'sync']],
    'failed'      => ['driver' => 'database-uuids', 'database' => env('DB_CONNECTION', 'sqlite'), 'table' => 'failed_jobs'],
];
