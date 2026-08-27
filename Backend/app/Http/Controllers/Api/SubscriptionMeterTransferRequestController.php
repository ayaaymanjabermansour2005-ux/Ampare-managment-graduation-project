<?php

namespace App\Http\Controllers\Api;

use App\Actions\SubscriptionMeterTransfer\ApproveSubscriptionMeterTransferRequestAction;
use App\Actions\SubscriptionMeterTransfer\CreateSubscriptionMeterTransferRequestAction;
use App\Actions\SubscriptionMeterTransfer\RejectSubscriptionMeterTransferRequestAction;
use App\DTOs\SubscriptionMeterTransfer\CreateSubscriptionMeterTransferRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionMeterTransfer\RejectSubscriptionMeterTransferRequest;
use App\Http\Requests\SubscriptionMeterTransfer\StoreSubscriptionMeterTransferRequest;
use App\Http\Resources\SubscriptionMeterTransferRequestResource;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Services\SubscriptionMeterTransferService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group طلبات نقل عداد الاشتراك
 */
class SubscriptionMeterTransferRequestController extends Controller
{
    use ApiResponse;

    public function __construct(protected SubscriptionMeterTransferService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionMeterTransferRequest::class);

        $requests = $this->service->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->string('status')->toString() ?: null
        );

        return $this->success(
            message: 'قائمة طلبات نقل العداد.',
            data: SubscriptionMeterTransferRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function store(StoreSubscriptionMeterTransferRequest $request, CreateSubscriptionMeterTransferRequestAction $action): JsonResponse
    {
        $subscription = Subscription::with('subscriberMeter.subscriber')
            ->findOrFail($request->validated('subscription_id'));

        $this->authorize('create', [SubscriptionMeterTransferRequest::class, $subscription]);

        $transferRequest = $action->execute(
            CreateSubscriptionMeterTransferRequestData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم إرسال طلب نقل العداد، بانتظار مراجعة مالك المولد.',
            data: new SubscriptionMeterTransferRequestResource($transferRequest),
            code: 201
        );
    }

    public function approve(
        SubscriptionMeterTransferRequest $subscriptionMeterTransfer,
        ApproveSubscriptionMeterTransferRequestAction $action
    ): JsonResponse {
        $this->authorize('approve', $subscriptionMeterTransfer);

        $transferRequest = $action->execute($subscriptionMeterTransfer, request()->user());

        return $this->success(
            message: 'تمت الموافقة على طلب نقل العداد وتنفيذ النقل بنجاح.',
            data: new SubscriptionMeterTransferRequestResource($transferRequest)
        );
    }

    public function reject(
        RejectSubscriptionMeterTransferRequest $request,
        SubscriptionMeterTransferRequest $subscriptionMeterTransfer,
        RejectSubscriptionMeterTransferRequestAction $action
    ): JsonResponse {
        $this->authorize('reject', $subscriptionMeterTransfer);

        $transferRequest = $action->execute(
            $subscriptionMeterTransfer,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return $this->success(message: 'تم رفض طلب نقل العداد.', data: new SubscriptionMeterTransferRequestResource($transferRequest));
    }
}
