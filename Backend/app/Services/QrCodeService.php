<?php

namespace App\Services;

use App\Models\Generator;
use App\Models\Invoice;
use App\Models\SubscriberMeter;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function invoiceVerificationUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'invoices.verify',
            now()->addYear(),
            ['invoice' => $invoice->id]
        );
    }

    public function invoiceVerificationQrBase64(Invoice $invoice): string
    {
        return $this->toBase64($this->invoiceVerificationUrl($invoice));
    }

    public function subscriberMeterQrBase64(SubscriberMeter $meter): string
    {
        return $this->toBase64($this->frontendUrl('subscriber-meters/'.$meter->id));
    }

    public function generatorQrBase64(Generator $generator): string
    {
        return $this->toBase64($this->frontendUrl('generators/'.$generator->id));
    }

    protected function frontendUrl(string $path): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/').'/'.ltrim($path, '/');
    }

    protected function toBase64(string $content): string
    {
        $svg = QrCode::format('svg')->size(220)->margin(1)->generate($content);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
