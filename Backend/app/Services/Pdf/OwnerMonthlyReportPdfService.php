<?php

namespace App\Services\Pdf;

use App\Models\User;
use App\Services\OwnerMonthlyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;

class OwnerMonthlyReportPdfService
{
    public function __construct(protected OwnerMonthlyReportService $reportService) {}

    public function download(User $owner, Carbon $month): Response
    {
        return $this->build($owner, $month)->download($this->fileName($owner, $month));
    }

    public function output(User $owner, Carbon $month): string
    {
        return $this->build($owner, $month)->output();
    }

    protected function build(User $owner, Carbon $month): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.owner-monthly-report', $this->reportService->build($owner, $month))
            ->setPaper('a4');
    }

    protected function fileName(User $owner, Carbon $month): string
    {
        return 'monthly-report-'.$owner->id.'-'.$month->format('Y-m').'.pdf';
    }
}
