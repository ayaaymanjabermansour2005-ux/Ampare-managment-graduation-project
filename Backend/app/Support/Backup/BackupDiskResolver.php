<?php

namespace App\Support\Backup;

/**
 * PROD-02: decides which spatie/laravel-backup destination disks apply.
 *
 * Pure/side-effect-free on purpose — config/backup.php calls this with the
 * raw env() values (config files are the one place env() calls belong per
 * Laravel convention), while everything else in the app depends on this
 * class directly and tests it with plain arguments instead of mutating
 * environment variables, which Laravel's immutable env repository does not
 * allow at runtime anyway.
 */
final class BackupDiskResolver
{
    /**
     * @return non-empty-list<string>
     */
    public static function resolve(?string $backupBucket, ?string $fallbackBucket, ?string $accessKeyId): array
    {
        $bucket = $backupBucket ?: $fallbackBucket;

        $hasOffsiteCredentials = filled($bucket) && filled($accessKeyId);

        return $hasOffsiteCredentials ? ['local', 's3-backups'] : ['local'];
    }
}
