<?php

namespace App\Actions\SubscriptionServiceRequest;

use App\Enums\ServiceRequestStatus;
use App\Models\SubscriptionServiceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelServiceRequestAction
{
    public function execute(SubscriptionServiceRequest $serviceRequest): SubscriptionServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest) {
            $serviceRequest = SubscriptionServiceRequest::lockForUpdate()->findOrFail($serviceRequest->id);

            if (! $serviceRequest->isPending()) {
                throw ValidationException::withMessages([
                    'service_request' => ['لا يمكن إلغاء طلب تمت مراجعته مسبقًا.'],
                ]);
            }

            $serviceRequest->forceFill(['status' => ServiceRequestStatus::Cancelled])->save();

            return $serviceRequest->fresh();
        });
    }
}
