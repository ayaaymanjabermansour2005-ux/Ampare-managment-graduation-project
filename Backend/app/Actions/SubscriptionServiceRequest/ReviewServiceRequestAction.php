<?php

namespace App\Actions\SubscriptionServiceRequest;

use App\DTOs\SubscriptionServiceRequest\ReviewServiceRequestData;
use App\Enums\ServiceRequestStatus;
use App\Models\SubscriptionOverride;
use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReviewServiceRequestAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function execute(SubscriptionServiceRequest $serviceRequest, ReviewServiceRequestData $data, User $user): SubscriptionServiceRequest
    {
        $approved = $data->decision === 'approved';

        return DB::transaction(function () use ($serviceRequest, $approved, $data, $user) {
            $serviceRequest = SubscriptionServiceRequest::lockForUpdate()->findOrFail($serviceRequest->id);

            if (! $serviceRequest->isPending()) {
                throw ValidationException::withMessages([
                    'service_request' => ['هذا الطلب تمت مراجعته مسبقًا.'],
                ]);
            }

            $serviceRequest->forceFill([
                'status' => $approved ? ServiceRequestStatus::Approved : ServiceRequestStatus::Rejected,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $data->reviewNote,
                'fee_amount' => $approved ? $data->feeAmount : null,
                'fee_currency' => $approved ? $data->feeCurrency : null,
            ])->save();

            if ($approved && $serviceRequest->extra_capacity_kw) {
                SubscriptionOverride::create([
                    'subscription_id' => $serviceRequest->subscription_id,
                    'service_request_id' => $serviceRequest->id,
                    'extra_capacity_kw' => $serviceRequest->extra_capacity_kw,
                    'starts_at' => $serviceRequest->starts_at,
                    'ends_at' => $serviceRequest->ends_at,
                ]);
            }

            if ($approved && $serviceRequest->fee_amount) {
                $this->invoiceService->createFromServiceRequest($serviceRequest);
            }

            return $serviceRequest->fresh(['subscription.generator', 'requestedBy', 'reviewedBy', 'override', 'invoice']);
        });
    }
}
