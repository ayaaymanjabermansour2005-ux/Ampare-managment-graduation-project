<?php

namespace App\Http\Controllers\Api;

use App\Actions\SubscriptionServiceRequest\CancelServiceRequestAction;
use App\Actions\SubscriptionServiceRequest\CreateServiceRequestAction;
use App\Actions\SubscriptionServiceRequest\ReviewServiceRequestAction;
use App\DTOs\SubscriptionServiceRequest\CreateServiceRequestData;
use App\DTOs\SubscriptionServiceRequest\ReviewServiceRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionServiceRequest\ReviewSubscriptionServiceRequestRequest;
use App\Http\Requests\SubscriptionServiceRequest\StoreSubscriptionServiceRequestRequest;
use App\Http\Resources\SubscriptionServiceRequestResource;
use App\Models\SubscriptionServiceRequest;
use App\Services\SubscriptionServiceRequestService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	 	الاشتراكات وطلبات الخدمة
 */
class SubscriptionServiceRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionServiceRequestService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionServiceRequest::class);

        $requests = $this->service->list(
            $request->user(),
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة طلبات الخدمة.',
            data: SubscriptionServiceRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function store(StoreSubscriptionServiceRequestRequest $request, CreateServiceRequestAction $action): JsonResponse
    {
        $this->authorize('create', SubscriptionServiceRequest::class);

        $serviceRequest = $action->execute(
            CreateServiceRequestData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم إرسال طلب الخدمة بنجاح، بانتظار مراجعة مالك المولد.',
            data: new SubscriptionServiceRequestResource($serviceRequest),
            code: 201
        );
    }

    public function show(SubscriptionServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);

        return $this->success(
            message: 'بيانات طلب الخدمة.',
            data: new SubscriptionServiceRequestResource(
                $serviceRequest->load(['subscription.generator', 'requestedBy', 'reviewedBy', 'override', 'invoice'])
            )
        );
    }

    public function review(
        ReviewSubscriptionServiceRequestRequest $request,
        SubscriptionServiceRequest $serviceRequest,
        ReviewServiceRequestAction $action
    ): JsonResponse {
        $this->authorize('review', $serviceRequest);

        $data = ReviewServiceRequestData::fromArray($request->validated());

        $serviceRequest = $action->execute($serviceRequest, $data, $request->user());

        return $this->success(
            message: $data->decision === 'approved' ? 'تمت الموافقة على طلب الخدمة.' : 'تم رفض طلب الخدمة.',
            data: new SubscriptionServiceRequestResource($serviceRequest)
        );
    }

    public function cancel(SubscriptionServiceRequest $serviceRequest, CancelServiceRequestAction $action): JsonResponse
    {
        $this->authorize('cancel', $serviceRequest);

        $serviceRequest = $action->execute($serviceRequest);

        return $this->success(
            message: 'تم إلغاء طلب الخدمة.',
            data: new SubscriptionServiceRequestResource($serviceRequest)
        );
    }
}
