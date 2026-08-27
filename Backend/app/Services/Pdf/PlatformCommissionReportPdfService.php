<?php

namespace App\Services\Pdf;

use App\Models\PlatformCommission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class PlatformCommissionReportPdfService
{
    public function download(User $owner, ?string $from, ?string $to): Response
    {
        $commissions = $this->query($owner, $from, $to)->get();

        return Pdf::loadView('pdf.commission-report', [
            'owner' => $owner,
            'commissions' => $commissions,
            'from' => $from ? Carbon::parse($from) : null,
            'to' => $to ? Carbon::parse($to) : null,
            'totalAmount' => $commissions->sum('commission_amount'),
        ])
            ->setPaper('a4')
            ->download('commission-report-'.$owner->id.'-'.now()->format('Y-m-d').'.pdf');
    }

    protected function query(User $owner, ?string $from, ?string $to)
    {
        return PlatformCommission::query()
            ->with('invoice.subscription.generator')
            ->where('owner_id', $owner->id)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest();
    }
}
