<?php

namespace App\Services;

use App\Enums\TechnicianStatus;
use App\Enums\TechnicianTaskStatus;
use App\Models\Generator;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TechnicianService
{
    public function list(User $user, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $activeTaskStatuses = array_map(
            fn (TechnicianTaskStatus $s) => $s->value,
            array_filter(TechnicianTaskStatus::cases(), fn ($s) => $s->isActive())
        );

        $query = Technician::query()
            ->with(['user', 'owner'])
            ->withAvg('ratings as rating_avg', 'rating')
            ->withCount(['tasks as active_tasks_count' => fn ($q) => $q->whereIn('status', $activeTaskStatuses)]);

        if (! $user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        if ($search) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function availableForGenerator(Generator $generator): Collection
    {
        $ownerTechnicians = Technician::where('owner_id', $generator->owner_id)
            ->where('status', TechnicianStatus::Active->value)
            ->with('user')
            ->get();

        return $ownerTechnicians->filter(function (Technician $technician) use ($generator) {
            $hasExplicitLinks = $technician->generators()->exists();

            return $hasExplicitLinks
                ? $technician->generators()->where('generators.id', $generator->id)->exists()
                : true;
        })->values();
    }

    public function create(array $data, User $owner): Technician
    {
        if (Technician::where('user_id', $data['user_id'])->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['يوجد ملف فني مرتبط بهذا المستخدم بالفعل.'],
            ]);
        }

        if (! $owner->isOwner()) {
            throw ValidationException::withMessages([
                'owner' => ['فقط مالك المولد يمكنه إنشاء فنيين تابعين له.'],
            ]);
        }

        $technician = Technician::create([
            'user_id' => $data['user_id'],
            'owner_id' => $owner->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return $technician->fresh('user');
    }

    public function update(Technician $technician, array $data): Technician
    {
        $isDeactivating = isset($data['status'])
            && TechnicianStatus::from($data['status']) !== TechnicianStatus::Active
            && $technician->status === TechnicianStatus::Active;

        if ($isDeactivating) {
            $this->assertNoActiveTasks($technician, 'تعطيل');
        }

        $technicianFields = array_intersect_key($data, array_flip(['status', 'notes']));
        if ($technicianFields !== []) {
            $technician->update($technicianFields);
        }

        $userFields = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
        if ($userFields !== [] && $technician->user) {
            $technician->user->update($userFields);
        }

        return $technician->fresh(['user', 'owner']);
    }

    public function delete(Technician $technician): void
    {
        $this->assertNoActiveTasks($technician, 'حذف');

        $technician->delete();
    }

    private function assertNoActiveTasks(Technician $technician, string $action): void
    {
        $activeStatuses = array_map(
            fn (TechnicianTaskStatus $s) => $s->value,
            array_filter(TechnicianTaskStatus::cases(), fn ($s) => $s->isActive())
        );

        $activeTasksCount = $technician->tasks()
            ->whereIn('status', $activeStatuses)
            ->count();

        if ($activeTasksCount > 0) {
            throw ValidationException::withMessages([
                'technician' => ["لا يمكن {$action} هذا الفني لأنه مرتبط بـ {$activeTasksCount} أمر شغل نشط. يجب إنهاء هذه المهام أولًا."],
            ]);
        }
    }

    public function linkToGenerator(Technician $technician, Generator $generator): void
    {
        $generator->technicians()->syncWithoutDetaching([$technician->id]);
    }

    public function unlinkFromGenerator(Technician $technician, Generator $generator): void
    {
        $generator->technicians()->detach($technician->id);
    }
}
