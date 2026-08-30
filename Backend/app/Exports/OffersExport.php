<?php

namespace App\Exports;

use App\Enums\OfferStatus;
use App\Enums\OfferTargetMode;
use App\Models\Offer;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * تصدير العروض/الخصومات — نفس منطق التصريح المستخدم في OfferService::list()
 * بالضبط (أدمن: الكل، مالك: عروضه فقط، مشترك: العروض المستهدفة له تحديدًا عبر
 * scopeVisibleToSubscriber/applyTargetVisibility هناك)، معاد كتابتها هون كـ
 * query() بدل استدعاء الخدمة مباشرة، لأن الـ Export بيحتاج Builder وليس
 * LengthAwarePaginator — لكنها نسخة طبق الأصل من شروط الفلترة هناك.
 */
class OffersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected bool $includeExpired = true,
    ) {}

    public function query(): Builder
    {
        $query = Offer::query()->with('owner');

        if ($this->user->isAdmin()) {
        } elseif ($this->user->isOwner()) {
            $query->where('owner_id', $this->user->id);
        } elseif ($this->user->isSubscriber()) {
            $subscriber = $this->user->subscriber;

            if (! $subscriber) {
                $query->whereRaw('1 = 0');
            } else {
                $ownerIds = $subscriber->subscriptions()
                    ->with('generator')
                    ->get()
                    ->pluck('generator.owner_id')
                    ->filter()
                    ->unique()
                    ->values();

                $query->whereIn('owner_id', $ownerIds)
                    ->where(function (Builder $q) use ($subscriber) {
                        $q->where('target_mode', OfferTargetMode::All->value)
                            ->orWhere(function (Builder $qq) use ($subscriber) {
                                $qq->where('target_mode', OfferTargetMode::Beneficiary->value)
                                    ->where('beneficiary_type', $subscriber->beneficiary_type->value);
                            })
                            ->orWhere(function (Builder $qq) use ($subscriber) {
                                $qq->where('target_mode', OfferTargetMode::Selected->value)
                                    ->whereHas('targetedSubscribers', fn ($q3) => $q3->where('subscribers.id', $subscriber->id));
                            });
                    });
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        if (! $this->includeExpired) {
            $query->where('status', OfferStatus::Active->value)
                ->where('end_date', '>=', now()->toDateString());
        }

        return $query
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('offer_id'),
            ExportLabel::heading('title'),
            ExportLabel::heading('discount'),
            ExportLabel::heading('target'),
            ExportLabel::heading('owner'),
            ExportLabel::heading('start_date'),
            ExportLabel::heading('end_date'),
            ExportLabel::heading('status'),
            ExportLabel::heading('created_at'),
        ];
    }

    public function map($offer): array
    {
        $discount = $offer->discount_type?->value === 'percentage'
            ? ((float) $offer->discount_value).'%'
            : (float) $offer->discount_value;

        return [
            $offer->id,
            $offer->title,
            $discount,
            ExportLabel::forEnum($offer->target_mode),
            $offer->owner?->name,
            $offer->start_date?->toDateString(),
            $offer->end_date?->toDateString(),
            ExportLabel::forEnum($offer->status),
            $offer->created_at?->toDateString(),
        ];
    }
}
