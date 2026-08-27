<?php

namespace App\Exports;

use App\Models\Fault;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * تصدير الأعطال — نفس شروط التصريح المستخدمة في FaultService::list() حرفيًا:
 * أدمن: بدون قيد. مالك: أعطال مولداته فقط (generator.owner_id). مشترك: أعطال
 * المولدات المرتبطة باشتراكاته فقط. فني: أعطال المولدات المسندة له فقط.
 * غير هيك: 1=0 (منع كامل).
 */
class FaultsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $status = null,
    ) {}

    public function query(): Builder
    {
        $query = Fault::query()->with(['generator', 'reporter', 'verifiedBy']);

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isOwner()) {
            $query->whereHas('generator', fn (Builder $q) => $q->where('owner_id', $this->user->id));
        } elseif ($this->user->isSubscriber()) {
            $query->whereHas(
                'generator.subscriptions.subscriberMeter.subscriber',
                fn (Builder $q) => $q->where('user_id', $this->user->id)
            );
        } elseif ($this->user->isTechnician()) {
            $query->whereHas(
                'generator.technicians',
                fn (Builder $q) => $q->where('technicians.user_id', $this->user->id)
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('generator', fn ($g) => $g->where('name', 'like', "%{$this->search}%"));
            });
        }

        return $query
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('reported_at');
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('fault_id'),
            ExportLabel::heading('title'),
            ExportLabel::heading('generator'),
            ExportLabel::heading('priority'),
            ExportLabel::heading('reported_by'),
            ExportLabel::heading('reported_at'),
            ExportLabel::heading('verified_by'),
            ExportLabel::heading('status'),
            ExportLabel::heading('resolved_at'),
        ];
    }

    public function map($fault): array
    {
        return [
            $fault->id,
            $fault->title,
            $fault->generator?->name,
            ExportLabel::forEnum($fault->priority),
            $fault->reporter?->name,
            $fault->reported_at?->toDateString(),
            $fault->verifiedBy?->name,
            ExportLabel::forEnum($fault->status),
            $fault->resolved_at?->toDateString(),
        ];
    }
}
