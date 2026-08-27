<?php

namespace App\Http\Controllers\Api;

use App\Actions\AiChat\AnalyzeGeneratorDiagnosticAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratorDiagnostic\StoreGeneratorDiagnosticReadingRequest;
use App\Http\Resources\FaultPredictionResource;
use App\Http\Resources\GeneratorDiagnosticReadingResource;
use App\Models\Generator;
use App\Models\GeneratorDiagnosticReading;
use App\Services\GeneratorDiagnosticService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group 	  المساعد الذكي والتشخيص
 */
class GeneratorDiagnosticController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GeneratorDiagnosticService $diagnosticService
    ) {}

    public function index(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $readings = $this->diagnosticService->history($generator)->load(['recordedBy', 'faultPrediction']);

        return $this->success(
            message: 'سجل قراءات المحرك.',
            data: GeneratorDiagnosticReadingResource::collection($readings)
        );
    }

    public function store(StoreGeneratorDiagnosticReadingRequest $request, Generator $generator): JsonResponse
    {
        $reading = $this->diagnosticService->record($generator, $request->validated(), $request->user());

        return $this->success(
            message: 'تم تسجيل القراءة بنجاح.',
            data: new GeneratorDiagnosticReadingResource($reading->load('recordedBy')),
            code: 201
        );
    }

    public function analyze(GeneratorDiagnosticReading $reading, AnalyzeGeneratorDiagnosticAction $action): JsonResponse
    {
        $this->authorize('record', $reading->generator);

        $prediction = $action->execute($reading);

        return $this->success(
            message: 'تم تحليل القراءة عبر الذكاء الاصطناعي. النتيجة بانتظار مراجعة مالك المولد.',
            data: new FaultPredictionResource($prediction),
            code: 201
        );
    }
}
