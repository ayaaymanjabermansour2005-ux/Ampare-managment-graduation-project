<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  	الدفعات ووسائل الدفع
 */
class PaymentMethodController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentMethodService $paymentMethodService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentMethod::class);

        $methods = $this->paymentMethodService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->integer('technician_id') ?: null
        );

        return $this->success(
            message: 'وسائل الدفع.',
            data: PaymentMethodResource::collection($methods)->response()->getData(true)
        );
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $method = $this->paymentMethodService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم إضافة وسيلة الدفع بنجاح.',
            data: new PaymentMethodResource($method),
            code: 201
        );
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('update', $paymentMethod);

        $method = $this->paymentMethodService->update($paymentMethod, $request->validated());

        return $this->success(
            message: 'تم تحديث وسيلة الدفع بنجاح.',
            data: new PaymentMethodResource($method)
        );
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('delete', $paymentMethod);

        $this->paymentMethodService->delete($paymentMethod);

        return $this->success(message: 'تم حذف وسيلة الدفع بنجاح.');
    }
}
