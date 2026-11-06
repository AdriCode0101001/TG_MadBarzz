<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Relativa a propósito: así las imágenes subidas (home, avisos) cargan igual si se abre el
            // sitio por localhost, por 127.0.0.1:8000, por un túnel o por el dominio real. Antes dependía
            // de APP_URL y, si no coincidía con la dirección usada para entrar, la imagen no cargaba.
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Disco privado: archivos NO accesibles por URL directa (QRs, fotos de perfil)
        'privado' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'throw'  => false,
        ],

        // Disco local de respaldos: storage/app/backups (fuera del public web root)
        'backup_local' => [
            'driver' => 'local',
            'root'   => storage_path('app/backups'),
            'throw'  => false,
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
            'report' => false,
        ],

        // Backblaze B2 — destino externo de respaldos (S3-compatible)
        // Usa driver s3 estándar; solo el endpoint apunta a B2.
        'b2' => [
            'driver'                  => 's3',
            'key'                     => env('B2_KEY_ID'),
            'secret'                  => env('B2_APPLICATION_KEY'),
            'region'                  => env('B2_REGION'),
            'bucket'                  => env('B2_BUCKET'),
            'endpoint'                => env('B2_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'throw'                   => false,
            'report'                  => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
