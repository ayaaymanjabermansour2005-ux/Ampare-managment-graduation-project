<?php

namespace App\Actions\Complaint;

use App\DTOs\Complaint\ResolveComplaintData;
use App\Enums\ComplaintStatus;
use App\Events\ComplaintResolved;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResolveComplaintAction
{
    public function execute(Complaint $complaint, ResolveComplaintData $data, User $user): Complaint
    {
        return DB::transaction(function () use ($complaint, $data, $user) {
            $complaint = Complaint::lockForUpdate()->findOrFail($complaint->id);

            if ($complaint->status === ComplaintStatus::Resolved) {
                throw ValidationException::withMessages([
                    'complaint' => ['هذه الشكوى محلولة بالفعل — لا يمكن تعديل حالتها.'],
                ]);
            }

            $isResolved = $data->status === ComplaintStatus::Resolved->value;

            $complaint->update([
                'status' => $data->status,
                'resolution_note' => $data->resolutionNote ?? $complaint->resolution_note,
                'resolved_by' => $isResolved ? $user->id : $complaint->resolved_by,
                'resolved_at' => $isResolved ? now() : $complaint->resolved_at,
            ]);

            $resolved = $complaint->fresh(['submitter', 'resolver', 'complainable']);

            if ($isResolved) {
                ComplaintResolved::dispatch($resolved);
            }

            return $resolved;
        });
    }
}
