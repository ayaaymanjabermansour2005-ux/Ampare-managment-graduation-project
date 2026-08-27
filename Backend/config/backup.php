<?php

use App\Support\Backup\BackupDiskResolver;
use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

// هذا الملف ناتج عن: php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

return [

    'backup' => [

        'name' => env('APP_NAME', 'ampare-management'),

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/private/attachments'),
                    base_path('storage/app/public'),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            'databases' => [
                'mysql',
            ],
        ],

        'database_dump_compressor' => null,
        'destination' => [
            'compression_method' => null,
            'compression_level' => 9,

            'filename_prefix' => '',

            // PROD-02: 'local' alone is a single point of failure — if the
            // host disk is lost, every backup is lost with it. The
            // 's3-backups' disk (config/filesystems.php) is added
            // automatically once real credentials exist (AWS_BACKUP_BUCKET
            // or AWS_BUCKET + AWS_ACCESS_KEY_ID), so a fresh/local/test
            // environment with no S3 credentials configured still backs up
            // to 'local' only, exactly as before — nothing here requires
            // fake or placeholder credentials to keep working.
            'disks' => BackupDiskResolver::resolve(
                env('AWS_BACKUP_BUCKET'),
                env('AWS_BUCKET'),
                env('AWS_ACCESS_KEY_ID'),
            ),
        ],
    ],

    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_ALERT_EMAIL', 'admin@ampare.test'),
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'ampare-management'),
            'disks' => BackupDiskResolver::resolve(
                env('AWS_BACKUP_BUCKET'),
                env('AWS_BUCKET'),
                env('AWS_ACCESS_KEY_ID'),
            ),
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
