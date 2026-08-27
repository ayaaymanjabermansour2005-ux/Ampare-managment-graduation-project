<?php

namespace App\Exports;

use App\Models\Subscription;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SubscriptionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $status = null,
    ) {}

    public function query(): Builder
    {
        $query = Subscription::query()->with([
            'subscriberMeter.subscriber.user',
            'generator.owner',
        ]);

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isSubscriber()) {
            $query->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $this->user->id));
        } elseif ($this->user->isOwner()) {
            $query->whereHas('generator', fn ($q) => $q->where('owner_id', $this->user->id));
        } elseif ($this->user->isTechnician()) {
            $query->whereHas('generator.technicians', fn ($q) => $q->where('technicians.user_id', $this->user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('subscriberMeter', fn ($meterQ) => $meterQ->where('meter_number', 'like', "%{$this->search}%"))
                    ->orWhereHas('subscriberMeter.subscriber.user', function ($userQ) {
                        $userQ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('subscription_id'),
            ExportLabel::heading('subscriber'),
            ExportLabel::heading('email'),
            ExportLabel::heading('generator'),
            ExportLabel::heading('generator_owner'),
            ExportLabel::heading('price_per_kw'),
            ExportLabel::heading('currency'),
            ExportLabel::heading('status'),
            ExportLabel::heading('start_date'),
            ExportLabel::heading('end_date'),
            ExportLabel::heading('created_at'),
        ];
    }

    public function map($subscription): array
    {
        return [
            $subscription->id,
            $subscription->subscriberMeter?->subscriber?->user?->name,
            $subscription->subscriberMeter?->subscriber?->user?->email,
            $subscription->generator?->name,
            $subscription->generator?->owner?->name,
            (float) $subscription->agreed_price_per_kw,
            $subscription->currency?->value,
            ExportLabel::forEnum($subscription->status),
            $subscription->start_date?->toDateString(),
            $subscription->end_date?->toDateString(),
            $subscription->created_at?->toDateString(),
        ];
    }
}
