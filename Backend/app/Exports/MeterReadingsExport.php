<?php

namespace App\Exports;

use App\Models\MeterReading;
use App\Models\Technician;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * تصدير قراءات العدادات — نفس شروط النطاق المستخدَمة حرفيًا في
 * MeterReadingService::list() (أدمن بدون قيد، مالك مقيَّد بمولداته، مشترك
 * بعداداته، فني بقراءاته المسندة لمولدات يعمل عليها).
 */
class MeterReadingsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $status = null,
        protected ?int $technicianId = null,
    ) {}

    public function query(): Builder
    {
        $query = MeterReading::query()->with([
            'subscription.generator',
            'subscription.subscriberMeter.subscriber.user',
            'creator',
        ]);

        if ($this->user->isAdmin()) {
            // بدون قيد.
        } elseif ($this->user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $this->user->id));
        } elseif ($this->user->isSubscriber()) {
            $query->whereHas('subscription.subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $this->user->id));
        } elseif ($this->user->isTechnician()) {
            $query->whereHas('subscription.generator.technicians', fn ($q) => $q->where('technicians.user_id', $this->user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($this->technicianId) {
            $technicianUserId = Technician::whereKey($this->technicianId)->value('user_id');
            $query->where('created_by', $technicianUserId);
        }

        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->whereHas(
                    'subscription.subscriberMeter.subscriber.user',
                    fn ($u) => $u->where('name', 'like', "%{$this->search}%")
                )->orWhereHas(
                    'subscription.generator',
                    fn ($g) => $g->where('name', 'like', "%{$this->search}%")
                );
            });
        }

        return $query
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('reading_date');
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('reading_id'),
            ExportLabel::heading('generator'),
            ExportLabel::heading('subscriber'),
            ExportLabel::heading('meter_number'),
            ExportLabel::heading('reading_date'),
            ExportLabel::heading('previous_reading'),
            ExportLabel::heading('current_reading'),
            ExportLabel::heading('consumed_kw'),
            ExportLabel::heading('status'),
            ExportLabel::heading('created_by'),
        ];
    }

    public function map($reading): array
    {
        return [
            $reading->id,
            $reading->subscription?->generator?->name,
            $reading->subscription?->subscriberMeter?->subscriber?->user?->name,
            $reading->subscription?->subscriberMeter?->meter_number,
            $reading->reading_date?->toDateString(),
            $reading->previous_reading,
            $reading->current_reading,
            $reading->consumed_kw,
            ExportLabel::forEnum($reading->status),
            $reading->creator?->name,
        ];
    }
}
