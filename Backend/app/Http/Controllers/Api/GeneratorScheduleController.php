<?php

namespace App\Http\Controllers\Api;

use App\Actions\GeneratorSchedule\CreateGeneratorScheduleAction;
use App\DTOs\GeneratorSchedule\CreateGeneratorScheduleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratorSchedule\StoreGeneratorScheduleRequest;
use App\Http\Requests\GeneratorSchedule\UpdateGeneratorScheduleRequest;
use App\Http\Resources\GeneratorScheduleResource;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class GeneratorScheduleController extends Controller
{
    use ApiResponse;

    public function index(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $schedules = $generator->schedules()
            ->where('ends_at', '>=', now()->subDay())
            ->with(['creator'])
            ->get();

        return $this->success(
            message: 'جدول تشغيل المولد.',
            data: GeneratorScheduleResource::collection($schedules)
        );
    }

    public function store(StoreGeneratorScheduleRequest $request, Generator $generator, CreateGeneratorScheduleAction $action): JsonResponse
    {
        $schedule = $action->execute(
            $generator,
            CreateGeneratorScheduleData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم نشر جدول التشغيل بنجاح.',
            data: new GeneratorScheduleResource($schedule),
            code: 201
        );
    }

    public function update(UpdateGeneratorScheduleRequest $request, GeneratorSchedule $generator_schedule): JsonResponse
    {
        $generator_schedule->update($request->validated());

        return $this->success(
            message: 'تم تحديث جدول التشغيل.',
            data: new GeneratorScheduleResource($generator_schedule->fresh(['generator', 'creator']))
        );
    }

    public function destroy(GeneratorSchedule $generator_schedule): JsonResponse
    {
        $this->authorize('delete', $generator_schedule);

        $generator_schedule->delete();

        return $this->success(message: 'تم حذف جدول التشغيل.');
    }
}
