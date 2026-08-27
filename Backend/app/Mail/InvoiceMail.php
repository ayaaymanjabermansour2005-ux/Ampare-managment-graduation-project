<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function build()
    {
        $invoice = $this->invoice;

        return $this
            ->subject('Invoice #'.$invoice->id)
            ->view('emails.invoice', [
                'invoiceId' => $invoice->id,
                'status' => $invoice->status->value,
                'statusLabel' => $invoice->status->label(),
                'finalAmount' => (float) $invoice->final_amount,
                'currency' => $invoice->currency,
                'generatorName' => $invoice->subscription?->generator?->name,
                'dueDate' => $invoice->due_date?->toDateString(),
                'issuedAt' => $invoice->created_at?->toDateString(),
            ]);
    }
}
