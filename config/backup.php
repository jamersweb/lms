<?php

return [
    'enabled' => env('BACKUP_ENABLED', false),
    'path' => env('BACKUP_PATH', storage_path('app/backups')),
    'mysqldump' => env('MYSQLDUMP_BINARY', 'mysqldump'),
];
