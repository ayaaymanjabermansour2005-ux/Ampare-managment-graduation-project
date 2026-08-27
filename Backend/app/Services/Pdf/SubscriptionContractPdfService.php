<?php

namespace App\Services\Pdf;

use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionContractPdfService
{
    protected const ELIGIBLE_STATUSES = ['active', 'suspended', 'cancelled'];

    public function __construct(protected ArabicPdfShaper $arabicPdfShaper) {}

    public function download(Subscription $subscription): Response
    {
        return $this->build($subscription)->download($this->fileName($subscription));
    }

    public function stream(Subscription $subscription): Response
    {
        return $this->build($subscription)->stream($this->fileName($subscription));
    }

    protected function build(Subscription $subscription): \Barryvdh\DomPDF\PDF
    {
        if (! in_array($subscription->status->value, self::ELIGIBLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'subscription' => ['لا يمكن إصدار عقد لاشتراك لم تتم الموافقة عليه بعد.'],
            ])->status(422);
        }

        $subscription->loadMissing([
            'generator.owner',
            'subscriberMeter.subscriber.user',
        ]);

        $html = view('pdf.subscription-contract', [
            'subscription' => $subscription,
        ])->render();

        if (app()->getLocale() === 'ar') {
            $html = $this->arabicPdfShaper->shapeHtml($html);
        }

        return Pdf::loadHTML($html)->setPaper('a4');
    }

    protected function fileName(Subscription $subscription): string
    {
        return 'contract-'.$subscription->id.'-'.Str::slug((string) $subscription->created_at?->format('Y-m-d')).'.pdf';
    }
}
