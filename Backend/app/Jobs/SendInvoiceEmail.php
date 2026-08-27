<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $invoiceId
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::with([
            'subscription.subscriberMeter.subscriber.user',
        ])->findOrFail($this->invoiceId);

        $subscriberUser = $invoice
            ->subscription
            ?->subscriberMeter
            ?->subscriber
            ?->user;

        if (! $subscriberUser?->email) {
            logger()->warning(
                "Invoice email skipped. User email missing. Invoice ID: {$invoice->id}"
            );

            return;
        }

        Mail::to($subscriberUser->email)
            ->send(new InvoiceMail($invoice));

        logger()->info(
            "Invoice email sent successfully. Invoice ID: {$invoice->id}"
        );
    }
}
