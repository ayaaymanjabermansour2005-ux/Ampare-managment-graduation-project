<?php

namespace App\Exports;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Generator;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GeneratorsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $status = null,
    ) {}

    public function query(): Builder
    {
        $query = Generator::query()
            ->withCount(['subscriptions' => fn($q) => $q->where('status', SubscriptionStatus::Active->value)])
            ->withSum(['invoices as monthly_revenue_ils' => function ($q) {
                $q->where('invoices.status', InvoiceStatus::Paid->value)
                    ->whereMonth('invoices.created_at', now()->month)
                    ->whereYear('invoices.created_at', now()->year);
            }], 'final_amount_ils')
            ->with(['owner', 'location.neighborhood', 'latestFuelReading']);

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isOwner()) {
            $query->where('owner_id', $this->user->id);
        } elseif ($this->user->isTechnician()) {
            $query->whereHas('technicians', fn($q) => $q->where('technicians.user_id', $this->user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('location', fn($locQ) => $locQ->where('city', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('generator_id'),
            ExportLabel::heading('generator_name'),
            ExportLabel::heading('owner'),
            ExportLabel::heading('city'),
            ExportLabel::heading('capacity_kw'),
            ExportLabel::heading('fuel_type'),
            ExportLabel::heading('fuel_percentage'),
            ExportLabel::heading('active_subscribers'),
            ExportLabel::heading('monthly_revenue_ils'),
            ExportLabel::heading('status'),
            ExportLabel::heading('notes'),
            ExportLabel::heading('added_at'),
        ];
    }

    public function map($generator): array
    {
        $fuelPercentage = null;
        if ($generator->tank_capacity_liters && $generator->latestFuelReading) {
            $fuelPercentage = round(
                ((float) $generator->latestFuelReading->tank_level_liters / (float) $generator->tank_capacity_liters) * 100,
                1
            );
        }

        return [
            $generator->id,
            $generator->name,
            $generator->owner?->name,
            $generator->location?->city,
            $generator->capacity_kw,
            ExportLabel::forEnum($generator->fuel_type),
            $fuelPercentage,
            $generator->subscriptions_count,
            (float) ($generator->monthly_revenue_ils ?? 0),
            ExportLabel::forEnum($generator->status),
            $generator->notes,
            $generator->created_at?->toDateString(),
        ];
    }
}
