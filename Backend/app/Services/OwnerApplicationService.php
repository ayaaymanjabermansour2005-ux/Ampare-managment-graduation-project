<?php

namespace App\Services;

use App\Enums\OwnerApplicationStatus;
use App\Models\OwnerApplication;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OwnerApplicationService
{
    /**
     * @return LengthAwarePaginator<int, OwnerApplication>
     */
    public function list(
        int $perPage,
        ?string $status = null,
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $sort = 'created_desc',
    ): LengthAwarePaginator {
        $query = OwnerApplication::query()->with(['reviewedBy', 'attachments', 'generatorNeighborhood']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        match ($sort) {
            'created_asc' => $query->oldest(),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $applications = $query->paginate($perPage);

        $this->attachDuplicateFlags($applications->getCollection());

        return $applications;
    }

    /**
     * @return array{pending: int, approved: int, rejected: int, all: int}
     */
    public function statusCounts(): array
    {
        $counts = OwnerApplication::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($counts[OwnerApplicationStatus::Pending->value] ?? 0);
        $approved = (int) ($counts[OwnerApplicationStatus::Approved->value] ?? 0);
        $rejected = (int) ($counts[OwnerApplicationStatus::Rejected->value] ?? 0);

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'all' => $pending + $approved + $rejected,
        ];
    }

    /**
     * @param  Collection<int, OwnerApplication>  $applications
     */
    private function attachDuplicateFlags(Collection $applications): void
    {
        $emails = $applications->pluck('email')->filter()->unique()->values();
        $phones = $applications->pluck('phone')->filter()->unique()->values();

        if ($emails->isEmpty() && $phones->isEmpty()) {
            return;
        }

        $existingUsers = User::query()
            ->where(function ($q) use ($emails, $phones) {
                if ($emails->isNotEmpty()) {
                    $q->orWhereIn('email', $emails);
                }
                if ($phones->isNotEmpty()) {
                    $q->orWhereIn('phone', $phones);
                }
            })
            ->get(['id', 'name', 'email', 'phone']);

        $byEmail = $existingUsers->filter(fn (User $u) => (bool) $u->email)->keyBy('email');
        $byPhone = $existingUsers->filter(fn (User $u) => (bool) $u->phone)->keyBy('phone');

        foreach ($applications as $application) {
            $emailMatch = $byEmail->get($application->email);
            $phoneMatch = $application->phone ? $byPhone->get($application->phone) : null;
            $match = $emailMatch ?? $phoneMatch;

            // بيانات المستخدم الحقيقي المطابق (بريد أولًا ثم هاتف) — تُستخدَم
            // بالواجهة لعرض/الانتقال لسجل المستخدم الموجود فعليًا، وليس تخمينًا.
            $application->setAttribute('is_duplicate_email', (bool) $emailMatch);
            $application->setAttribute('is_duplicate_phone', (bool) $phoneMatch);
            $application->setAttribute('duplicate_user_id', $match?->id);
            $application->setAttribute('duplicate_user_name', $match?->name);
            $application->setAttribute('duplicate_user_email', $match?->email);
        }
    }
}
