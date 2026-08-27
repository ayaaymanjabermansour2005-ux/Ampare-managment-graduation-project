<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fuel\FuelReportRequest;
use App\Http\Requests\Fuel\StoreFuelPurchaseRequest;
use App\Http\Requests\Fuel\StoreFuelReadingRequest;
use App\Http\Resources\FuelPurchaseResource;
use App\Http\Resources\FuelReadingResource;
use App\Models\Generator;
use App\Services\FuelService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group 	  الوقود
 */
class FuelController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FuelService $fuelService
    ) {}

    public function purchases(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'سجل شراء الوقود.',
            data: FuelPurchaseResource::collection($this->fuelService->purchases($generator))
        );
    }

    public function storePurchase(StoreFuelPurchaseRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('record', $generator);

        $purchase = $this->fuelService->recordPurchase($generator, $request->validated(), $request->user());

        return $this->success(
            message: 'تم تسجيل عملية شراء الوقود بنجاح.',
            data: new FuelPurchaseResource($purchase),
            code: 201
        );
    }

    public function readings(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'سجل قراءات الخزان.',
            data: FuelReadingResource::collection($this->fuelService->readings($generator))
        );
    }

    public function storeReading(StoreFuelReadingRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('record', $generator);

        $reading = $this->fuelService->recordReading($generator, $request->validated(), $request->user());

        return $this->success(
            message: 'تم تسجيل قراءة الخزان بنجاح.',
            data: new FuelReadingResource($reading),
            code: 201
        );
    }

    public function consumption(FuelReportRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $result = $this->fuelService->consumptionBetween(
            $generator,
            $request->validated('from'),
            $request->validated('to')
        );

        if (! $result) {
            return $this->error(
                message: 'لا توجد قراءتان كافيتان لحساب الاستهلاك بهذه الفترة.',
                code: 422
            );
        }

        return $this->success(message: 'تقرير استهلاك الوقود.', data: $result);
    }

    public function costPerKwh(FuelReportRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $result = $this->fuelService->costPerKwh(
            $generator,
            $request->validated('from'),
            $request->validated('to')
        );

        if (! $result) {
            return $this->error(
                message: 'بيانات غير كافية لحساب تكلفة الكيلوواط (تحتاج قراءتَي وقود وقراءات عدادات بنفس الفترة).',
                code: 422
            );
        }

        return $this->success(message: 'تكلفة الكيلوواط الفعلية.', data: $result);
    }

    public function status(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $status = $this->fuelService->checkLowFuelLevel($generator);

        if (! $status) {
            return $this->error(
                message: 'لا توجد بيانات كافية (سعة الخزان أو قراءة حديثة) لعرض الحالة.',
                code: 422
            );
        }

        return $this->success(message: 'حالة الوقود الحالية.', data: $status);
    }
}
