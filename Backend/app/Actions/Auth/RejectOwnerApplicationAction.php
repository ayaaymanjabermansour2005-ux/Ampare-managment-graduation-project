<?php

namespace App\Actions\Auth;

use App\Enums\OwnerApplicationStatus;
use App\Models\OwnerApplication;
use App\Models\User;
use App\Notifications\OwnerApplicationRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RejectOwnerApplicationAction
{
    public function execute(OwnerApplication $application, User $reviewer, ?string $reason = null): OwnerApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $reason) {
            $application = OwnerApplication::lockForUpdate()->findOrFail($application->id);

            if (! $application->isPending()) {
                throw ValidationException::withMessages([
                    'status' => ['هذا الطلب سبق أن تمت مراجعته.'],
                ]);
            }

            $application->forceFill([
                'status' => OwnerApplicationStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            activity()
                ->causedBy($reviewer)
                ->performedOn($application)
                ->withProperties(['reason' => $reason])
                ->log('owner_application_rejected');

            $route = Notification::route('mail', $application->email);

            if (filled($application->phone)) {
                $route->route('whatsapp', $application->phone);
            }

            $route->notify(new OwnerApplicationRejectedNotification($application, $reason));

            return $application->fresh(['reviewedBy']);
        });
    }
}
