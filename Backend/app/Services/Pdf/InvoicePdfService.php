<?php

namespace App\Services\Pdf;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class InvoicePdfService
{
    public function __construct(
        protected QrCodeService $qrCodeService,
        protected InvoiceService $invoiceService,
        protected ArabicPdfShaper $arabicPdfShaper,
    ) {}

    public function download(Invoice $invoice): Response
    {
        return $this->build($invoice)->download($this->fileName($invoice));
    }

    public function stream(Invoice $invoice): Response
    {
        return $this->build($invoice)->stream($this->fileName($invoice));
    }

    protected function build(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing([
            'subscription.generator.owner',
            'subscription.subscriberMeter.subscriber.user',
            'payments' => fn ($q) => $q->where('status', PaymentStatus::Paid->value),
            'commission',
            'appliedOffer',
        ]);

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
            'remainingBalance' => $this->invoiceService->remainingBalance($invoice),
            'verificationQr' => $this->qrCodeService->invoiceVerificationQrBase64($invoice),
        ])->render();

        if (app()->getLocale() === 'ar') {
            $html = $this->arabicPdfShaper->shapeHtml($html);
        }

        return Pdf::loadHTML($html)->setPaper('a4');
    }

    protected function fileName(Invoice $invoice): string
    {
        return 'invoice-'.$invoice->id.'-'.Str::slug((string) $invoice->created_at?->format('Y-m-d')).'.pdf';
    }
}
