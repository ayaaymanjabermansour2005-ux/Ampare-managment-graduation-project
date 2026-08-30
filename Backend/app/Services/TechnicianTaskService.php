<?php

namespace App\Services;

use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TechnicianTaskService
{
    public function list(User $user, int $perPage = 15, ?array $types = null, ?string $status = null, ?int $technicianId = null, ?string $search = null): LengthAwarePaginator
    {
        $query = TechnicianTask::query()->with(['generator', 'technician.user', 'requestedBy', 'rating']);

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isTechnician()) {
            $query->whereHas('technician', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($types) {
            $query->whereIn('type', $types);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%")
                    ->orWhereHas('generator', fn ($g) => $g->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('technician.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * إجماليات أوامر الشغل حسب الحالة عبر كل السجلات المطابقة لصلاحية
     * المستخدم — وليس فقط الصفحة الحالية، حتى تبقى بطاقات KPI دقيقة بعد
     * تجاوز أول صفحة.
     *
     * @return array{total: int, active: int, pending_review: int, approved: int}
     */
    public function stats(User $user): array
    {
        $query = TechnicianTask::query();

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isTechnician()) {
            $query->whereHas('technician', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        $activeStatuses = ['pending', 'assigned', 'on_the_way', 'in_progress', 'waiting_parts'];

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'active' => (int) collect($activeStatuses)->sum(fn ($s) => $counts[$s] ?? 0),
            'pending_review' => (int) ($counts['submitted'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
        ];
    }
}
