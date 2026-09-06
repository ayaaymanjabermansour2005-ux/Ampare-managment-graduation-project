<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ComplaintService
{
    public function list(
        User $user,
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?string $channel = null,
        ?string $priority = null,
        ?int $assignedTo = null,
    ): LengthAwarePaginator {
        $query = Complaint::query()->with(['submitter', 'resolver', 'assignedTo', 'complainable']);

        if (! $user->isAdmin()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('submitted_by', $user->id);

                if ($user->isOwner()) {
                    $q->orWhereHasMorph('complainable', [Generator::class], fn ($g) => $g->where('owner_id', $user->id))
                        ->orWhereHasMorph('complainable', [Fault::class], fn ($g) => $g->whereHas('generator', fn ($gg) => $gg->where('owner_id', $user->id)))
                        ->orWhereHasMorph('complainable', [Subscription::class], fn ($g) => $g->whereHas('generator', fn ($gg) => $gg->where('owner_id', $user->id)))
                        ->orWhereHasMorph('complainable', [Invoice::class], fn ($g) => $g->whereHas('subscription.generator', fn ($gg) => $gg->where('owner_id', $user->id)))
                        ->orWhereHasMorph('complainable', [Payment::class], fn ($g) => $g->whereHas('invoice.subscription.generator', fn ($gg) => $gg->where('owner_id', $user->id)));
                }
            });
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        return $query->latest()->paginate($perPage);
    }

    public function assign(Complaint $complaint, ?int $assignedTo): Complaint
    {
        $complaint->update(['assigned_to' => $assignedTo]);

        return $complaint->fresh(['submitter', 'resolver', 'assignedTo', 'complainable']);
    }
}
