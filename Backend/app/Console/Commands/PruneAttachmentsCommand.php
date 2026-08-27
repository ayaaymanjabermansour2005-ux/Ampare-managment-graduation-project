<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Console\Command;

class PruneAttachmentsCommand extends Command
{
    protected $signature = 'attachments:prune';

    protected $description = 'حذف نهائي (ملف + سجل) للمرفقات المحذوفة ناعمًا بعد انتهاء مدة الاحتفاظ';

    public function handle(AttachmentService $attachmentService): int
    {
        $retentionDays = config('attachments.retention_days');

        $attachments = Attachment::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($retentionDays))
            ->get();

        foreach ($attachments as $attachment) {
            $attachmentService->forceDelete($attachment);
        }

        $this->info("تم حذف {$attachments->count()} مرفق نهائيًا (بعد {$retentionDays} يوم من الحذف الناعم).");

        return self::SUCCESS;
    }
}
