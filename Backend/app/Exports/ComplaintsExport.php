<?php

namespace App\Exports;

use App\Models\Complaint;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * تصدير الشكاوى — نفس شروط التصريح المستخدمة في ComplaintService::list()
 * حرفيًا: أدمن بدون قيد، وأي مستخدم تاني يشوف فقط الشكاوى اللي قدّمها هو
 * (submitted_by) أو — لو مالك — الشكاوى المرتبطة بكيان يملكه (مولد/عطل/
 * اشتراك/فاتورة/دفعة عبر علاقة owner_id بالنهاية) عبر نفس whereHasMorph.
 */
class ComplaintsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $status = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
    ) {}

    public function query(): Builder
    {
        $query = Complaint::query()->with(['submitter', 'resolver', 'complainable']);

        if (! $this->user->isAdmin()) {
            $query->where(function (Builder $q) {
                $q->where('submitted_by', $this->user->id);

                if ($this->user->isOwner()) {
                    $q->orWhereHasMorph('complainable', [Generator::class], fn ($g) => $g->where('owner_id', $this->user->id))
                        ->orWhereHasMorph('complainable', [Fault::class], fn ($g) => $g->whereHas('generator', fn ($gg) => $gg->where('owner_id', $this->user->id)))
                        ->orWhereHasMorph('complainable', [Subscription::class], fn ($g) => $g->whereHas('generator', fn ($gg) => $gg->where('owner_id', $this->user->id)))
                        ->orWhereHasMorph('complainable', [Invoice::class], fn ($g) => $g->whereHas('subscription.generator', fn ($gg) => $gg->where('owner_id', $this->user->id)))
                        ->orWhereHasMorph('complainable', [Payment::class], fn ($g) => $g->whereHas('invoice.subscription.generator', fn ($gg) => $gg->where('owner_id', $this->user->id)));
                }
            });
        }

        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
            });
        }

        return $query
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('complaint_id'),
            ExportLabel::heading('subject'),
            ExportLabel::heading('submitted_by'),
            ExportLabel::heading('related_to'),
            ExportLabel::heading('status'),
            ExportLabel::heading('resolved_by'),
            ExportLabel::heading('resolved_at'),
            ExportLabel::heading('created_at'),
        ];
    }

    public function map($complaint): array
    {
        $relatedKey = $complaint->complainable_type_label;

        return [
            $complaint->id,
            $complaint->subject,
            $complaint->submitter?->name,
            $relatedKey ? __('exports.related_to.'.$relatedKey) : '',
            ExportLabel::forEnum($complaint->status),
            $complaint->resolver?->name,
            $complaint->resolved_at?->toDateString(),
            $complaint->created_at?->toDateString(),
        ];
    }
}
