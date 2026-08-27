<?php

namespace App\Exports;

use App\Models\PlatformCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlatformCommissionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $from = null,
        protected ?string $to = null,
    ) {}

    public function query(): Builder
    {
        $query = PlatformCommission::query()->with(['invoice.subscription.generator', 'owner']);

        if (! $this->user->isAdmin()) {
            $query->where('owner_id', $this->user->id);
        }

        return $query
            ->when($this->from, fn ($q) => $q->whereDate('platform_commissions.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('platform_commissions.created_at', '<=', $this->to))
            ->latest();
    }

    public function headings(): array
    {
        return ['رقم الفاتورة', 'المولد', 'الأونر', 'نسبة العمولة', 'قيمة العمولة', 'الحالة', 'تاريخ الاستحقاق', 'تاريخ التحويل'];
    }

    public function map($commission): array
    {
        return [
            $commission->invoice_id,
            $commission->invoice?->subscription?->generator?->name,
            $commission->owner?->name,
            (float) $commission->commission_rate,
            (float) $commission->commission_amount,
            $commission->status->label(),
            $commission->earned_at?->toDateString(),
            $commission->paid_at?->toDateString(),
        ];
    }
}
