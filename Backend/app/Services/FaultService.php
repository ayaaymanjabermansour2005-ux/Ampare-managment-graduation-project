<?php

namespace App\Services;

use App\Actions\TechnicianTask\CreateTechnicianTaskAction;
use App\DTOs\TechnicianTask\CreateTechnicianTaskData;
use App\Enums\FaultPriority;
use App\Enums\FaultRepairMethod;
use App\Enums\FaultSource;
use App\Enums\FaultStatus;
use App\Enums\TechnicianTaskType;
use App\Events\FaultReported;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaultService
{
    public function __construct(
        protected CreateTechnicianTaskAction $createTechnicianTaskAction
    ) {}

    public function list(User $user, int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Fault::query()->with(['generator', 'reporter']);

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('generator', fn (Builder $q) => $q->where('owner_id', $user->id));
        } elseif ($user->isSubscriber()) {
            $query->whereHas(
                'generator.subscriptions.subscriberMeter.subscriber',
                fn (Builder $q) => $q->where('user_id', $user->id)
            );
        } elseif ($user->isTechnician()) {
            $query->whereHas(
                'generator.technicians',
                fn (Builder $q) => $q->where('technicians.user_id', $user->id)
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('generator', fn ($g) => $g->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest('reported_at')->paginate($perPage);
    }

    public function create(array $data, User $user): Fault
    {
        $generator = Generator::findOrFail($data['generator_id']);

        $fault = Fault::create([
            'generator_id' => $generator->id,
            'reported_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? FaultPriority::Medium->value,
            'source' => $user->isSubscriber() ? FaultSource::SubscriberReport->value : FaultSource::Manual->value,
            'status' => FaultStatus::PendingVerification,
            'reported_at' => now(),
        ]);

        FaultReported::dispatch($fault);

        return $fault;
    }

    public function verify(Fault $fault, User $user, bool $isValid): Fault
    {
        return DB::transaction(function () use ($fault, $user, $isValid) {
            $fault = Fault::lockForUpdate()->findOrFail($fault->id);

            if ($fault->status !== FaultStatus::PendingVerification) {
                throw ValidationException::withMessages([
                    'fault' => ['هذا العطل تم التحقق منه مسبقًا.'],
                ]);
            }

            $fault->update([
                'status' => $isValid ? FaultStatus::Verified : FaultStatus::Rejected,
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            return $fault->fresh(['generator']);
        });
    }

    public function decideRepair(
        Fault $fault,
        User $user,
        FaultRepairMethod $method,
        ?int $technicianId,
        ?string $instructions,
    ): Fault {
        return DB::transaction(function () use ($fault, $user, $method, $technicianId, $instructions) {
            $fault = Fault::lockForUpdate()->findOrFail($fault->id);

            if ($fault->status !== FaultStatus::Verified) {
                throw ValidationException::withMessages([
                    'fault' => ['يجب التحقق من العطل أولًا قبل اتخاذ قرار الإصلاح.'],
                ]);
            }

            if ($method === FaultRepairMethod::OwnerFixed) {
                $fault->update([
                    'repair_method' => $method,
                    'status' => FaultStatus::Closed,
                    'resolved_at' => now(),
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                ]);

                return $fault->fresh(['generator']);
            }

            $fault->update([
                'repair_method' => $method,
                'status' => FaultStatus::InRepair,
            ]);

            $this->createTechnicianTaskAction->execute(
                new CreateTechnicianTaskData(
                    generatorId: $fault->generator_id,
                    technicianId: $technicianId,
                    type: TechnicianTaskType::FaultRepair->value,
                    taskableType: Fault::class,
                    taskableId: $fault->id,
                    instructions: $instructions,
                ),
                $user
            );

            return $fault->fresh(['generator', 'technicianTasks']);
        });
    }

    public function delete(Fault $fault): void
    {
        DB::transaction(function () use ($fault) {
            $fault = Fault::lockForUpdate()->findOrFail($fault->id);

            $hasActiveTask = $fault->technicianTasks()
                ->get()
                ->contains(fn ($task) => $task->isActive());

            if ($hasActiveTask) {
                throw ValidationException::withMessages([
                    'fault' => ['لا يمكن حذف عطل مرتبط بمهمة فني نشطة. أنهِ أو ألغِ المهمة أولًا.'],
                ]);
            }

            $fault->delete();
        });
    }

    public function overrideStatus(Fault $fault, FaultStatus $status, string $reason): Fault
    {
        // FIX (Medium — Race Condition، اتساقًا): DB::transaction + lockForUpdate.
        return DB::transaction(function () use ($fault, $status, $reason) {
            $fault = Fault::lockForUpdate()->findOrFail($fault->id);

            $fault->update([
                'status' => $status,
                'admin_override_reason' => $reason,
            ]);

            return $fault->fresh(['generator']);
        });
    }
}
