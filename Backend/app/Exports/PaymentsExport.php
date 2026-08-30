<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
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
        $query = Payment::query()->with([
            'invoice.subscription.generator',
            'invoice.subscription.subscriberMeter.subscriber.user',
            'paymentMethod',
        ]);

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isOwner()) {
            $query->whereHas('invoice.subscription.generator', fn ($q) => $q->where('owner_id', $this->user->id));
        } elseif ($this->user->isSubscriber()) {
            $query->whereHas('invoice.subscription.subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $this->user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($this->from, fn ($q) => $q->whereDate('payments.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('payments.created_at', '<=', $this->to))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('payment_id'),
            ExportLabel::heading('invoice_id'),
            ExportLabel::heading('source'),
            ExportLabel::heading('amount'),
            ExportLabel::heading('currency'),
            ExportLabel::heading('amount_ils'),
            ExportLabel::heading('exchange_rate'),
            ExportLabel::heading('payment_method'),
            ExportLabel::heading('status'),
            ExportLabel::heading('paid_at'),
            ExportLabel::heading('submitted_at'),
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->invoice_id,
            ExportLabel::forEnum($payment->source),
            (float) $payment->amount,
            $payment->currency,
            (float) $payment->amount_ils,
            $payment->exchange_rate ? (float) $payment->exchange_rate : '—',
            $payment->paymentMethod?->type ? ExportLabel::forEnum($payment->paymentMethod->type) : __('exports.values.admin_adjustment'),
            ExportLabel::forEnum($payment->status),
            $payment->paid_at?->toDateString(),
            $payment->created_at?->toDateString(),
        ];
    }
}
