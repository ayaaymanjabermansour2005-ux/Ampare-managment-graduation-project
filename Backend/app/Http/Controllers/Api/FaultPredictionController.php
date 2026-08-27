<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaultPrediction\StoreFaultPredictionRequest;
use App\Http\Resources\FaultPredictionResource;
use App\Http\Resources\FaultResource;
use App\Models\FaultPrediction;
use App\Services\FaultPredictionService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  			الأعطال والتوقعات الذكية
 */
class FaultPredictionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FaultPredictionService $predictionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FaultPrediction::class);
        $predictions = $this->predictionService->list(
            $request->user(),
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة توقعات الأعطال.',
            data: FaultPredictionResource::collection($predictions)->response()->getData(true)
        );
    }

    public function store(StoreFaultPredictionRequest $request): JsonResponse
    {
        $this->authorize('create', FaultPrediction::class);

        $prediction = $this->predictionService->create($request->validated());

        return $this->success(
            message: 'تم تسجيل التوقع بنجاح.',
            data: new FaultPredictionResource($prediction),
            code: 201
        );
    }

    public function show(FaultPrediction $faultPrediction): JsonResponse
    {
        $this->authorize('view', $faultPrediction);

        return $this->success(
            message: 'بيانات التوقع.',
            data: new FaultPredictionResource($faultPrediction->load('confirmedFault'))
        );
    }

    public function confirm(Request $request, FaultPrediction $faultPrediction): JsonResponse
    {
        $this->authorize('confirm', $faultPrediction);

        $fault = $this->predictionService->confirm($faultPrediction, $request->user());

        return $this->success(
            message: 'تم تأكيد التوقع وتحويله لعطل فعلي.',
            data: new FaultResource($fault->load(['generator', 'verifiedBy'])),
            code: 201
        );
    }

    public function dismiss(FaultPrediction $faultPrediction): JsonResponse
    {
        $this->authorize('dismiss', $faultPrediction);

        $prediction = $this->predictionService->dismiss($faultPrediction);

        return $this->success(
            message: 'تم تجاهل التوقع.',
            data: new FaultPredictionResource($prediction)
        );
    }
}
