<?php

namespace App\Actions\SubscriptionMeterTransfer;

use App\Enums\SubscriptionMeterTransferStatus;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectSubscriptionMeterTransferRequestAction
{
    public function execute(SubscriptionMeterTransferRequest $transferRequest, User $reviewer, string $reason): SubscriptionMeterTransferRequest
    {
        return DB::transaction(function () use ($transferRequest, $reviewer, $reason) {
            $transferRequest = SubscriptionMeterTransferRequest::lockForUpdate()->findOrFail($transferRequest->id);

            if (! $transferRequest->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذا الطلب ليس بانتظار المراجعة.'],
                ]);
            }

            $transferRequest->forceFill([
                'status' => SubscriptionMeterTransferStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $transferRequest->fresh(['subscription.generator', 'fromMeter', 'toMeter', 'requestedBy', 'reviewedBy']);
        });
    }
}
