<?php

namespace App\Services;

use App\Enums\PlatformCommissionStatus;
use App\Models\PlatformCommission;
use App\Models\User;
use App\Support\Eloquent\FreshOrFail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformCommissionService
{
    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = PlatformCommission::query()->with(['invoice.subscription.generator', 'owner']);

        if (! $user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * إجماليات العمولات (مستحقة/محوّلة/قيد الانتظار) عبر كل السجلات المطابقة
     * لصلاحية المستخدم — وليس فقط الصفحة الحالية المعروضة بالجدول، حتى لا
     * تُضلِّل بطاقة "الإجمالي المكتسب" بعد تجاوز أول 15 سجل.
     *
     * @return array{earned: float, paid: float, pending: float}
     */
    public function summary(User $user): array
    {
        $query = PlatformCommission::query();

        if (! $user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        $totals = $query
            ->selectRaw('status, SUM(commission_amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'earned' => (float) ($totals[PlatformCommissionStatus::Earned->value] ?? 0),
            'paid' => (float) ($totals[PlatformCommissionStatus::Paid->value] ?? 0),
            'pending' => (float) ($totals[PlatformCommissionStatus::Pending->value] ?? 0),
        ];
    }

    public function markPaid(PlatformCommission $commission): PlatformCommission
    {
        return DB::transaction(function () use ($commission) {
            $commission = PlatformCommission::lockForUpdate()->findOrFail($commission->id);

            if ($commission->status !== PlatformCommissionStatus::Earned) {
                throw ValidationException::withMessages([
                    'status' => ['لا يمكن تحويل عمولة لم تُستحق بعد (الفاتورة غير مسدَّدة بالكامل).'],
                ]);
            }

            $commission->forceFill([
                'status' => PlatformCommissionStatus::Paid,
                'paid_at' => now(),
            ])->save();

            return FreshOrFail::reload($commission);
        });
    }
}
