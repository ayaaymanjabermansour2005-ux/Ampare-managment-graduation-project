<?php

namespace App\Console\Commands;

use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Console\Command;

class ResetDemoAccounts extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'إعادة ضبط بيانات الحسابات التجريبية (زائر) لحالتها الأصلية';

    public function handle(): int
    {
        $this->info('جارِ إعادة ضبط الحسابات التجريبية...');

        app(DemoAccountsSeeder::class)->run();

        $this->info('تم بنجاح ✅');

        return self::SUCCESS;
    }
}
