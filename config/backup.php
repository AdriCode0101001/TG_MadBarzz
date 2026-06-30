<?php

return [

    'backup' => [

        'name' => env('APP_NAME', 'MadBarzz'),

        'source' => [
            'files' => [
                /*
                 * Solo se respaldan los archivos subidos por los usuarios (privados).
                 * El código fuente vive en Git y no necesita respaldo aquí.
                 */
                'include' => [
                    storage_path('app/private'),
                ],

                'exclude' => [],

                'follow_links' => false,

                'ignore_unreadable_directories' => true,

                'relative_path' => storage_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        // gzip no está disponible en Windows; el ZIP del backup ya comprime.
        // En Linux/VPS puedes cambiar esto a \Spatie\DbDumper\Compressors\GzipCompressor::class
        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level'  => 9,
            'filename_prefix'    => '',

            /*
             * Destinos de backup:
             *  - backup_local : siempre activo (copia local en storage/app/backups)
             *  - b2           : Backblaze B2 — se activa cuando B2_BUCKET está definido en .env
             *
             * Para activar B2, define B2_KEY_ID, B2_APPLICATION_KEY, B2_BUCKET,
             * B2_REGION y B2_ENDPOINT en el .env del VPS (ver .env.example).
             */
            'disks' => array_filter([
                'backup_local',
                env('B2_BUCKET') ? 'b2' : null,
            ]),
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Encriptar el ZIP con contraseña (define BACKUP_ARCHIVE_PASSWORD en .env)
        'password'   => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'tries'       => 3,
        'retry_delay' => 60,
    ],

    // -----------------------------------------------------------------------
    // Notificaciones
    // -----------------------------------------------------------------------
    'notifications' => [
        'notifications' => [
            // Solo notificar en fallo o backup corrupto (evitar spam en cada éxito)
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class        => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class       => ['mail'],

            // Éxito: descomenta si quieres confirmación diaria
            // \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            // Dirección del administrador que recibe alertas de backup
            'to' => env('BACKUP_MAIL_TO', 'admin@tudominio.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@tudominio.com'),
                'name'    => env('MAIL_FROM_NAME', 'MadBarzz Backup'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK', ''),
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => env('BACKUP_DISCORD_WEBHOOK', ''),
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    // -----------------------------------------------------------------------
    // Monitor de salud
    // -----------------------------------------------------------------------
    'monitor_backups' => array_filter([
        [
            'name'  => env('APP_NAME', 'MadBarzz'),
            'disks' => ['backup_local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class         => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 2048,
            ],
        ],
        // Monitor de B2 solo cuando está configurado
        env('B2_BUCKET') ? [
            'name'  => env('APP_NAME', 'MadBarzz'),
            'disks' => ['b2'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class         => 2,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 10240,
            ],
        ] : null,
    ]),

    // -----------------------------------------------------------------------
    // Política de retención
    // Diarios: última semana completa
    // Semanales: último mes
    // Mensuales: últimos 3 meses
    // -----------------------------------------------------------------------
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'                      => 7,
            'keep_daily_backups_for_days'                    => 7,
            'keep_weekly_backups_for_weeks'                  => 4,
            'keep_monthly_backups_for_months'                => 3,
            'keep_yearly_backups_for_years'                  => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 2048,
        ],

        'tries'       => 2,
        'retry_delay' => 60,
    ],

];
