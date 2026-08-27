<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PruneIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:prune {--days=7 : عمر المفتاح بالأيام قبل حذفه}';

    protected $description = 'حذف مفاتيح Idempotency القديمة (لم تعد مطلوبة لمنع التكرار).';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = IdempotencyKey::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("تم حذف {$count} مفتاح idempotency أقدم من {$days} يوم.");

        return self::SUCCESS;
    }
}
