<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Document storage disk
    |--------------------------------------------------------------------------
    |
    | Default `local` in development and tests. Set DOCUMENT_DISK=s3 in cloud.
    | Never use the public disk: bytes stay private, metadata stays in the DB.
    |
    */

    'disk' => env('DOCUMENT_DISK', env('FILESYSTEM_DISK', 'local')),

    'max_kilobytes' => (int) env('DOCUMENT_MAX_KILOBYTES', 10240),

    'temporary_url_minutes' => (int) env('DOCUMENT_TEMPORARY_URL_MINUTES', 5),

    /**
     * @var list<string>
     */
    'allowed_mimetypes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'text/plain',
        'text/csv',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    /**
     * @var list<string>
     */
    'allowed_extensions' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'txt',
        'csv',
        'doc',
        'docx',
        'xls',
        'xlsx',
    ],

];
