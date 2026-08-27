<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentType;
use App\Enums\MeterReadingStatus;
use App\Exports\MeterReadingsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MeterReading\RejectMeterReadingRequest;
use App\Http\Requests\MeterReading\StoreMeterReadingAttachmentRequest;
use App\Http\Requests\MeterReading\StoreMeterReadingRequest;
use App\Http\Requests\MeterReading\UpdateMeterReadingRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Models\Subscription;
use App\Services\AttachmentService;
use App\Services\MeterReadingService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group   	القراءات والفواتير
 */
class MeterReadingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MeterReadingService $meterReadingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MeterReading::class);

        $readings = $this->meterReadingService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->input('status'),
            $request->integer('technician_id') ?: null
        );

        return $this->success(
            message: 'قائمة قراءات العدادات.',
            data: MeterReadingResource::collection($readings)->response()->getData(true)
        );
    }

    public function store(StoreMeterReadingRequest $request): JsonResponse
    {
        $this->authorize('create', MeterReading::class);

        $reading = $this->meterReadingService->create($request->validated(), $request->user());

        $message = $reading->status->value === MeterReadingStatus::Approved->value
            ? 'تم تسجيل القراءة وإصدار الفاتورة بنجاح.'
            : 'تم تسجيل القراءة بنجاح، وهي الآن بانتظار اعتماد صاحب المولد قبل إصدار الفاتورة.';

        return $this->success(
            message: $message,
            data: new MeterReadingResource($reading),
            code: 201
        );
    }

    public function show(MeterReading $meterReading): JsonResponse
    {
        $this->authorize('view', $meterReading);

        return $this->success(
            message: 'بيانات القراءة.',
            data: new MeterReadingResource($meterReading->load('creator', 'approver', 'invoice'))
        );
    }

    public function update(UpdateMeterReadingRequest $request, MeterReading $meterReading): JsonResponse
    {
        $this->authorize('update', $meterReading);

        $reading = $this->meterReadingService->update($meterReading, $request->validated());

        return $this->success(
            message: 'تم تعديل القراءة بنجاح.',
            data: new MeterReadingResource($reading)
        );
    }

    public function destroy(MeterReading $meterReading): JsonResponse
    {
        $this->authorize('delete', $meterReading);

        $this->meterReadingService->delete($meterReading);

        return $this->success(message: 'تم حذف القراءة بنجاح.');
    }

    public function approve(MeterReading $meterReading): JsonResponse
    {
        $this->authorize('approve', $meterReading);

        $reading = $this->meterReadingService->approve($meterReading, request()->user());

        return $this->success(
            message: 'تم اعتماد القراءة وإصدار الفاتورة بنجاح.',
            data: new MeterReadingResource($reading)
        );
    }

    public function reject(RejectMeterReadingRequest $request, MeterReading $meterReading): JsonResponse
    {
        $this->authorize('reject', $meterReading);

        $reading = $this->meterReadingService->reject($meterReading, $request->user(), $request->validated('reason'));

        return $this->success(
            message: 'تم رفض القراءة.',
            data: new MeterReadingResource($reading)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', MeterReading::class);

        return Excel::download(
            new MeterReadingsExport(
                $request->user(),
                $request->input('search'),
                $request->input('status'),
                $request->integer('technician_id') ?: null
            ),
            'meter-readings-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function overdueSubscribers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MeterReading::class);

        return $this->success(
            message: 'الاشتراكات المتأخرة عن تسجيل قراءة.',
            data: $this->meterReadingService->overdueSubscribers($request->user())
        );
    }

    public function history(Request $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        return $this->success(
            message: 'سجل استهلاك الاشتراك.',
            data: $this->meterReadingService->history($subscription)
        );
    }

    public function storeAttachment(
        StoreMeterReadingAttachmentRequest $request,
        MeterReading $meterReading,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('manageAttachments', $meterReading);

        $attachment = $attachmentService->upload(
            model: $meterReading,
            file: $request->file('file'),
            documentType: DocumentType::MeterReadingPhoto->value,
            user: $request->user(),
            description: $request->validated('description'),
        );

        return $this->success(
            message: 'تم رفع صورة القراءة بنجاح.',
            data: new AttachmentResource($attachment->load('uploader')),
            code: 201
        );
    }
}
