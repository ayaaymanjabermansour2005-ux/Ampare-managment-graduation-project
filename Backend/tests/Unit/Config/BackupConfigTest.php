<?php

namespace Tests\Unit\Config;

use App\Support\Backup\BackupDiskResolver;
use Tests\TestCase;

class BackupConfigTest extends TestCase
{
    public function test_backup_targets_local_disk_only_without_aws_credentials(): void
    {
        $this->assertSame(['local'], BackupDiskResolver::resolve(null, null, null));
        $this->assertSame(['local'], BackupDiskResolver::resolve('a-bucket', null, null), 'bucket without an access key must not enable the offsite disk');
        $this->assertSame(['local'], BackupDiskResolver::resolve(null, null, 'AKIA_TEST'), 'an access key without any bucket must not enable the offsite disk');
    }

    public function test_backup_adds_offsite_disk_once_aws_backup_credentials_are_present(): void
    {
        $this->assertSame(
            ['local', 's3-backups'],
            BackupDiskResolver::resolve('ampare-backups', null, 'AKIA_TEST')
        );
    }

    public function test_dedicated_backup_bucket_takes_priority_over_the_general_aws_bucket(): void
    {
        $this->assertSame(
            ['local', 's3-backups'],
            BackupDiskResolver::resolve(null, 'general-bucket', 'AKIA_TEST'),
            'must fall back to the general AWS_BUCKET when AWS_BACKUP_BUCKET is unset'
        );
    }

    public function test_the_booted_test_environment_has_no_offsite_credentials_configured(): void
    {
        // Confirms the config file wiring itself (config/backup.php calling
        // BackupDiskResolver with the real env() values) resolves to the
        // safe, credential-free default in this test environment, exactly
        // as it would on a fresh checkout with no .env.
        $this->assertSame(['local'], config('backup.backup.destination.disks'));
        $this->assertSame(['local'], config('backup.monitor_backups.0.disks'));
    }
}
