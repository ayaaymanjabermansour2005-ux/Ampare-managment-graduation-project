<?php

namespace App\Jobs;

use App\Mail\OwnerMonthlyReportMail;
use App\Models\User;
use App\Services\Pdf\OwnerMonthlyReportPdfService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOwnerMonthlyReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $ownerId, protected string $month) {}

    public function handle(OwnerMonthlyReportPdfService $pdfService): void
    {
        $owner = User::find($this->ownerId);

        if (! $owner?->email) {
            return;
        }

        $month = Carbon::parse($this->month);

        Mail::to($owner->email)->send(
            new OwnerMonthlyReportMail($owner, $pdfService->output($owner, $month), $month)
        );
    }
}
