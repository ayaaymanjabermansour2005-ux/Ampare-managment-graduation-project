<?php

namespace App\Exports;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GeneratorOwnersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected ?string $search = null,
        protected ?string $status = null,
    ) {}

    public function query(): Builder
    {
        return User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'generator_owner'))
            ->with('plan')
            ->withCount('generators')
            ->with(['generators' => function ($q) {
                $q->withCount(['subscriptions as active_subscriptions_count' => fn($sq) => $sq->where('status', SubscriptionStatus::Active->value)])
                    ->withSum(['invoices as monthly_revenue_ils' => function ($sq) {
                        $sq->where('invoices.status', InvoiceStatus::Paid->value)
                            ->whereMonth('invoices.created_at', now()->month)
                            ->whereYear('invoices.created_at', now()->year);
                    }], 'final_amount_ils');
            }])
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'الاسم',
            'الإيميل',
            'رقم الهاتف',
            'عدد المولدات',
            'إجمالي المشتركين',
            'الإيراد الشهري (₪)',
            'الخطة',
            'الحالة',
            'تاريخ الانضمام',
        ];
    }

    public function map($owner): array
    {
        return [
            $owner->name,
            $owner->email,
            $owner->phone,
            $owner->generators_count,
            $owner->generators->sum('active_subscriptions_count'),
            (float) $owner->generators->sum('monthly_revenue_ils'),
            $owner->plan?->name,
            $owner->status?->label(),
            $owner->created_at?->toDateString(),
        ];
    }
}
