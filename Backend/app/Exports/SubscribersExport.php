<?php

namespace App\Exports;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SubscribersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected ?string $search = null,
        protected ?string $status = null,
        protected ?string $subscriptionStatus = null,
    ) {}

    public function query(): Builder
    {
        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->with([
                'subscriber.subscriptions.generator',
                'subscriber.subscriptions.invoices' => fn ($q) => $q->whereIn('invoices.status', [
                    InvoiceStatus::Pending->value,
                    InvoiceStatus::Overdue->value,
                    InvoiceStatus::PartiallyPaid->value,
                ]),
            ]);

        // FIX (تدقيق شامل — الجولة الثالثة): كان الفلتر subscription_status
        // المُرسَل فعليًا من شاشة المشتركين (نشط له اشتراك / بدون اشتراك /
        // مقفول) يُتجاهَل بصمت هون — نفس منطق UserService::list()/UsersExport
        // حرفيًا، بدل مفهوم "status" المختلف كليًا (حالة الحساب العامة).
        if ($this->subscriptionStatus === 'active') {
            $query->whereHas(
                'subscriber.subscriptions',
                fn ($q) => $q->where('subscriptions.status', SubscriptionStatus::Active->value)
            );
        } elseif ($this->subscriptionStatus === 'none') {
            $query->whereDoesntHave(
                'subscriber.subscriptions',
                fn ($q) => $q->where('subscriptions.status', SubscriptionStatus::Active->value)
            );
        } elseif ($this->subscriptionStatus === 'locked') {
            $query->whereNotNull('locked_until')->where('locked_until', '>', now());
        }

        return $query
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('name'),
            ExportLabel::heading('email'),
            ExportLabel::heading('phone'),
            ExportLabel::heading('beneficiary_type'),
            ExportLabel::heading('linked_generators_count'),
            ExportLabel::heading('active_subscriptions'),
            ExportLabel::heading('outstanding_balance_ils'),
            ExportLabel::heading('status'),
            ExportLabel::heading('joined_at'),
        ];
    }

    public function map($subscriber): array
    {
        $subs = $subscriber->subscriber?->subscriptions ?? collect();

        return [
            $subscriber->name,
            $subscriber->email,
            $subscriber->phone,
            ExportLabel::forEnum($subscriber->subscriber?->beneficiary_type),
            $subs->pluck('generator_id')->unique()->count(),
            $subs->where('status', SubscriptionStatus::Active)->count(),
            (float) $subs->flatMap->invoices->sum('final_amount_ils'),
            ExportLabel::forEnum($subscriber->status),
            $subscriber->created_at?->toDateString(),
        ];
    }
}
