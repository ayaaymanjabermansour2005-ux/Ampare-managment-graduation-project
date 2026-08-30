<?php

namespace App\Http\Controllers\Api;

use App\Actions\AiChat\SendAiChatMessageAction;
use App\Actions\AiChat\StartAiChatSessionAction;
use App\Actions\AiChat\SubmitChatAsFaultPredictionAction;
use App\Actions\AiChat\SubmitChatAsFaultReportAction;
use App\DTOs\AiChat\StartAiChatSessionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AiChat\SendAiChatMessageRequest;
use App\Http\Requests\AiChat\StoreAiChatSessionRequest;
use App\Http\Resources\AiChatMessageResource;
use App\Http\Resources\AiChatSessionResource;
use App\Http\Resources\FaultPredictionResource;
use App\Http\Resources\FaultResource;
use App\Http\Resources\GeneratorResource;
use App\Models\AiChatSession;
use App\Services\AiChatService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  المساعد الذكي والتشخيص
 */
class AiChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiChatService $aiChatService
    ) {}

    public function availableGenerators(Request $request): JsonResponse
    {
        return $this->success(
            message: 'المولدات المتاحة للاستفسار عنها.',
            data: GeneratorResource::collection(
                $this->aiChatService->availableGeneratorsFor($request->user())
            )
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AiChatSession::class);

        $sessions = $this->aiChatService->listFor(
            $request->user(),
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة محادثاتك مع المساعد الذكي.',
            data: AiChatSessionResource::collection($sessions)->response()->getData(true)
        );
    }

    public function show(AiChatSession $aiChatSession): JsonResponse
    {
        $this->authorize('view', $aiChatSession);

        return $this->success(
            message: 'تفاصيل المحادثة.',
            data: new AiChatSessionResource(
                $aiChatSession->load(['generator', 'messages.attachments', 'faultPrediction', 'fault'])
            )
        );
    }

    public function store(StoreAiChatSessionRequest $request, StartAiChatSessionAction $action): JsonResponse
    {
        $this->authorize('create', AiChatSession::class);

        $session = $action->execute(
            StartAiChatSessionData::fromArray($request->validated()),
            $request->user(),
            $request->file('attachment')
        );

        return $this->success(
            message: 'تم بدء محادثة جديدة مع المساعد الذكي.',
            data: new AiChatSessionResource($session),
            code: 201
        );
    }

    public function sendMessage(
        SendAiChatMessageRequest $request,
        AiChatSession $aiChatSession,
        SendAiChatMessageAction $action
    ): JsonResponse {
        $this->authorize('view', $aiChatSession);

        $reply = $action->execute(
            $aiChatSession,
            $request->validated('message'),
            $request->file('attachment')
        );

        return $this->success(
            message: 'تم الرد.',
            data: new AiChatMessageResource($reply),
            code: 201
        );
    }

    public function submitAsPrediction(
        AiChatSession $aiChatSession,
        SubmitChatAsFaultPredictionAction $action
    ): JsonResponse {
        $this->authorize('submitAsPrediction', $aiChatSession);

        $prediction = $action->execute($aiChatSession);

        return $this->success(
            message: 'تم رفع المحادثة كطلب مراجعة عطل محتمل. بانتظار تحقق مالك المولد.',
            data: new FaultPredictionResource($prediction->load('generator')),
            code: 201
        );
    }

    public function submitAsFaultReport(
        AiChatSession $aiChatSession,
        SubmitChatAsFaultReportAction $action
    ): JsonResponse {
        $this->authorize('submitAsFaultReport', $aiChatSession);

        $fault = $action->execute($aiChatSession);

        return $this->success(
            message: 'تم إرسال بلاغ العطل لمالك المولد بنجاح.',
            data: new FaultResource($fault),
            code: 201
        );
    }
}
