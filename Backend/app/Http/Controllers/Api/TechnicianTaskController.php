<?php

namespace App\Http\Controllers\Api;

use App\Actions\TechnicianTask\AssignTechnicianTaskAction;
use App\Actions\TechnicianTask\CancelTechnicianTaskAction;
use App\Actions\TechnicianTask\CreateTechnicianTaskAction;
use App\Actions\TechnicianTask\MarkTaskOnTheWayAction;
use App\Actions\TechnicianTask\MarkTaskWaitingPartsAction;
use App\Actions\TechnicianTask\RateTechnicianTaskAction;
use App\Actions\TechnicianTask\ReviewTechnicianTaskAction;
use App\Actions\TechnicianTask\StartTechnicianTaskAction;
use App\Actions\TechnicianTask\SubmitTechnicianTaskAction;
use App\DTOs\TechnicianRating\CreateTechnicianRatingData;
use App\DTOs\TechnicianTask\CreateTechnicianTaskData;
use App\DTOs\TechnicianTask\ReviewTechnicianTaskData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianRating\StoreTechnicianRatingRequest;
use App\Http\Requests\TechnicianTask\AssignTechnicianTaskRequest;
use App\Http\Requests\TechnicianTask\ReviewTechnicianTaskRequest;
use App\Http\Requests\TechnicianTask\StoreTechnicianTaskRequest;
use App\Http\Requests\TechnicianTask\SubmitTechnicianTaskRequest;
use App\Http\Resources\TechnicianRatingResource;
use App\Http\Resources\TechnicianTaskResource;
use App\Models\TechnicianTask;
use App\Services\TechnicianTaskService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				الفنيين وأوامر الشغل
 */
class TechnicianTaskController extends Controller
{
    use ApiResponse;

    public function __construct(protected TechnicianTaskService $taskService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TechnicianTask::class);

        $types = $request->filled('type')
            ? array_filter(array_map('trim', explode(',', (string) $request->string('type'))))
            : null;

        return $this->success(
            message: 'قائمة أوامر الشغل.',
            data: TechnicianTaskResource::collection(
                $this->taskService->list(
                    $request->user(),
                    PerPageResolver::resolve($request),
                    $types,
                    $request->string('status')->toString() ?: null,
                    $request->integer('technician_id') ?: null,
                    $request->string('search')->toString() ?: null
                )
            )->response()->getData(true)
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TechnicianTask::class);

        return $this->success(
            message: 'إحصائيات أوامر الشغل.',
            data: $this->taskService->stats($request->user())
        );
    }

    public function store(StoreTechnicianTaskRequest $request, CreateTechnicianTaskAction $action): JsonResponse
    {
        $this->authorize('create', TechnicianTask::class);

        $task = $action->execute(
            CreateTechnicianTaskData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم إنشاء أمر الشغل بنجاح.',
            data: new TechnicianTaskResource($task),
            code: 201
        );
    }

    public function show(TechnicianTask $technicianTask): JsonResponse
    {
        $this->authorize('view', $technicianTask);

        return $this->success(
            message: 'تفاصيل أمر الشغل.',
            data: new TechnicianTaskResource($technicianTask->load(['generator', 'technician.user', 'requestedBy', 'rating']))
        );
    }

    public function assign(
        AssignTechnicianTaskRequest $request,
        TechnicianTask $technicianTask,
        AssignTechnicianTaskAction $action
    ): JsonResponse {
        $this->authorize('assign', $technicianTask);

        $task = $action->execute(
            $technicianTask,
            $request->validated('technician_id'),
            $request->user()
        );

        return $this->success(message: 'تم تعيين الفني على المهمة.', data: new TechnicianTaskResource($task));
    }

    public function onTheWay(TechnicianTask $technicianTask, MarkTaskOnTheWayAction $action): JsonResponse
    {
        $this->authorize('onTheWay', $technicianTask);

        $task = $action->execute($technicianTask);

        return $this->success(message: 'تم تسجيل تحرّك الفني نحو الموقع.', data: new TechnicianTaskResource($task));
    }

    public function start(TechnicianTask $technicianTask, StartTechnicianTaskAction $action): JsonResponse
    {
        $this->authorize('start', $technicianTask);

        $task = $action->execute($technicianTask);

        return $this->success(message: 'تم بدء تنفيذ المهمة.', data: new TechnicianTaskResource($task));
    }

    public function waitingParts(TechnicianTask $technicianTask, MarkTaskWaitingPartsAction $action): JsonResponse
    {
        $this->authorize('markWaitingParts', $technicianTask);

        $task = $action->execute($technicianTask);

        return $this->success(message: 'تم تسجيل انتظار قطع الغيار.', data: new TechnicianTaskResource($task));
    }

    public function submit(
        SubmitTechnicianTaskRequest $request,
        TechnicianTask $technicianTask,
        SubmitTechnicianTaskAction $action
    ): JsonResponse {
        $this->authorize('submit', $technicianTask);

        $task = $action->execute($technicianTask, $request->validated('completion_notes'));

        return $this->success(message: 'تم إرسال المهمة للمراجعة.', data: new TechnicianTaskResource($task));
    }

    public function review(
        ReviewTechnicianTaskRequest $request,
        TechnicianTask $technicianTask,
        ReviewTechnicianTaskAction $action
    ): JsonResponse {
        $this->authorize('review', $technicianTask);

        $data = ReviewTechnicianTaskData::fromArray($request->validated());

        $task = $action->execute($technicianTask, $data, $request->user());

        return $this->success(
            message: $data->decision === 'approved' ? 'تم اعتماد المهمة.' : 'تم رفض المهمة.',
            data: new TechnicianTaskResource($task)
        );
    }

    public function cancel(TechnicianTask $technicianTask, CancelTechnicianTaskAction $action): JsonResponse
    {
        $this->authorize('cancel', $technicianTask);

        $task = $action->execute($technicianTask);

        return $this->success(message: 'تم إلغاء المهمة.', data: new TechnicianTaskResource($task));
    }

    public function rate(StoreTechnicianRatingRequest $request, TechnicianTask $technician_task, RateTechnicianTaskAction $action): JsonResponse
    {
        $this->authorize('rate', $technician_task);

        $rating = $action->execute(
            $technician_task,
            CreateTechnicianRatingData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم تقييم الفني بنجاح.',
            data: new TechnicianRatingResource($rating),
            code: 201
        );
    }
}
