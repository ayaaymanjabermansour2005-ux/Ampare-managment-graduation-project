<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoicesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $from = null,
        protected ?string $to = null,
        protected ?string $status = null,
    ) {}

    public function query(): Builder
    {
        $query = Invoice::query()->with([
            'subscription.generator.owner',
            'subscription.subscriberMeter.subscriber.user',
        ]);

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $this->user->id));
        } elseif ($this->user->isSubscriber()) {
            $query->whereHas('subscription.subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $this->user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($this->from, fn ($q) => $q->whereDate('invoices.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('invoices.created_at', '<=', $this->to))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('invoice_id'),
            ExportLabel::heading('generator'),
            ExportLabel::heading('subscriber'),
            ExportLabel::heading('base_amount'),
            ExportLabel::heading('discount_amount'),
            ExportLabel::heading('final_amount'),
            ExportLabel::heading('currency'),
            ExportLabel::heading('final_amount_ils'),
            ExportLabel::heading('due_date'),
            ExportLabel::heading('status'),
            ExportLabel::heading('issued_at'),
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->id,
            $invoice->subscription?->generator?->name,
            $invoice->subscription?->subscriberMeter?->subscriber?->user?->name,
            (float) $invoice->amount,
            (float) $invoice->discount_amount,
            (float) $invoice->final_amount,
            $invoice->currency,
            (float) $invoice->final_amount_ils,
            $invoice->due_date?->toDateString(),
            ExportLabel::forEnum($invoice->status),
            $invoice->created_at?->toDateString(),
        ];
    }
}
