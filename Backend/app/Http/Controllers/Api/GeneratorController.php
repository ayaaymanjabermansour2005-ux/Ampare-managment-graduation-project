<?php

namespace App\Http\Controllers\Api;

use App\Actions\Generator\TransferGeneratorOwnershipAction;
use App\Enums\FaultStatus;
use App\Enums\SubscriptionStatus;
use App\Exports\GeneratorsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Generator\AvailableGeneratorsRequest;
use App\Http\Requests\Generator\RejectGeneratorRequest;
use App\Http\Requests\Generator\StoreGeneratorAttachmentRequest;
use App\Http\Requests\Generator\StoreGeneratorRequest;
use App\Http\Requests\Generator\TransferGeneratorOwnershipRequest;
use App\Http\Requests\Generator\UpdateGeneratorRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\FaultResource;
use App\Http\Resources\GeneratorHealthReportResource;
use App\Http\Resources\GeneratorResource;
use App\Http\Resources\TechnicianResource;
use App\Http\Resources\TechnicianTaskResource;
use App\Models\Generator;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\GeneratorService;
use App\Services\GeneratorTimelineService;
use App\Services\QrCodeService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group إدارة المولدات
 */
class GeneratorController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GeneratorService $generatorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Generator::class);
        $generators = $this->generatorService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->input('status'),
            $request->input('city'),
            $request->integer('owner_id') ?: null
        );

        return $this->success(
            message: 'قائمة المولدات.',
            data: GeneratorResource::collection($generators)->response()->getData(true)
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Generator::class);

        return $this->success(
            message: 'إحصائيات المولدات.',
            data: $this->generatorService->stats($request->user())
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Generator::class);

        return Excel::download(
            new GeneratorsExport(
                $request->user(),
                $request->input('search'),
                $request->input('status')
            ),
            'generators-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function store(StoreGeneratorRequest $request): JsonResponse
    {
        $this->authorize('create', Generator::class);

        $generator = $this->generatorService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم إضافة المولد بنجاح.',
            data: new GeneratorResource($generator->load(['owner', 'location.neighborhood'])),
            code: 201
        );
    }

    public function show(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        // FIX: GeneratorResource بيصدّر active_subscriptions_count عبر
        // whenCounted، بس show() ما كانت عم تعمل withCount/loadCount أبداً
        // (بعكس GeneratorService::list())، فكانت القيمة دايمًا مفقودة وصفحة
        // GeneratorDetailView ما كانت تعرض بطاقة "المشتركين النشطين" أبداً.
        $generator->load(['owner', 'location.neighborhood']);
        $generator->loadCount([
            'subscriptions' => fn ($q) => $q->where('status', SubscriptionStatus::Active),
        ]);

        return $this->success(
            message: 'بيانات المولد.',
            data: new GeneratorResource($generator)
        );
    }

    public function update(UpdateGeneratorRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('update', $generator);

        $generator = $this->generatorService->update($generator, $request->validated(), $request->user());

        return $this->success(
            message: 'تم تحديث بيانات المولد بنجاح.',
            data: new GeneratorResource($generator)
        );
    }

    public function transferOwnership(
        TransferGeneratorOwnershipRequest $request,
        Generator $generator,
        TransferGeneratorOwnershipAction $action
    ): JsonResponse {
        $this->authorize('transferOwnership', $generator);

        $newOwner = User::findOrFail($request->validated('owner_id'));

        $generator = $action->execute($generator, $newOwner, $request->user());

        return $this->success(
            message: 'تم نقل ملكية المولد بنجاح.',
            data: new GeneratorResource($generator)
        );
    }

    public function destroy(Generator $generator): JsonResponse
    {
        $this->authorize('delete', $generator);

        $this->generatorService->delete($generator);

        return $this->success(message: 'تم حذف المولد بنجاح.');
    }

    public function available(AvailableGeneratorsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Generator::class);

        $validated = $request->validated();

        $generators = $this->generatorService->available(
            $request->user(),
            $validated['subscriber_meter_id'] ?? null,
            $validated['schedule'] ?? null,
            $validated['service_start_time'] ?? null,
            $validated['service_end_time'] ?? null,
            isset($validated['requested_capacity_kw']) ? (float) $validated['requested_capacity_kw'] : null
        );

        return $this->success(
            message: 'المولدات المتاحة للاشتراك.',
            data: GeneratorResource::collection($generators)
        );
    }

    public function qrCode(Generator $generator, QrCodeService $qrCodeService): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'رمز QR للمولد.',
            data: ['qr' => $qrCodeService->generatorQrBase64($generator)]
        );
    }

    public function attachments(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'مرفقات المولد.',
            data: AttachmentResource::collection(
                $generator->attachments()->with('uploader')->latest()->get()
            )
        );
    }

    /**
     * الفنيون المرتبطون حاليًا بهذا المولد بشكل دائم (Attach/Detach) — عبر
     * جدول الربط generator_technician، وليس مهام الصيانة المؤقتة.
     */
    public function technicians(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'الفنيون المرتبطون بالمولد.',
            data: TechnicianResource::collection(
                $generator->technicians()->with('user')->get()
            )
        );
    }

    public function storeAttachment(
        StoreGeneratorAttachmentRequest $request,
        Generator $generator,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('manageAttachments', $generator);

        $attachment = $attachmentService->upload(
            model: $generator,
            file: $request->file('file'),
            documentType: $request->validated('document_type'),
            user: $request->user(),
            description: $request->validated('description'),
        );

        return $this->success(
            message: 'تم رفع المرفق بنجاح.',
            data: new AttachmentResource($attachment->load('uploader')),
            code: 201
        );
    }

    public function healthReports(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'تقارير صحة المولد.',
            data: GeneratorHealthReportResource::collection($generator->healthReports)
        );
    }

    public function timeline(Generator $generator, GeneratorTimelineService $timelineService): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'السجل الزمني للمولد.',
            data: $timelineService->build($generator)
        );
    }

    public function quickScan(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        $generator->load(['owner', 'location.neighborhood']);

        return $this->success(
            message: 'بيانات المسح السريع.',
            data: [
                'generator' => new GeneratorResource($generator),
                'open_faults' => FaultResource::collection(
                    $generator->faults()
                        ->whereIn('status', [
                            FaultStatus::PendingVerification->value,
                            FaultStatus::Verified->value,
                            FaultStatus::InRepair->value,
                        ])
                        ->latest('reported_at')
                        ->limit(10)
                        ->get()
                ),
                'recent_technician_tasks' => TechnicianTaskResource::collection(
                    $generator->technicianTasks()
                        ->with('technician.user')
                        ->latest()
                        ->limit(10)
                        ->get()
                ),
            ]
        );
    }

    public function verify(Generator $generator): JsonResponse
    {
        $this->authorize('verify', $generator);

        $verified = $this->generatorService->verify($generator, request()->user());

        return $this->success(
            message: 'تم اعتماد المولد بنجاح.',
            data: new GeneratorResource($verified)
        );
    }

    public function reject(RejectGeneratorRequest $request, Generator $generator): JsonResponse
    {
        $this->authorize('verify', $generator);

        $rejected = $this->generatorService->reject($generator, $request->user(), $request->validated('reason'));

        return $this->success(
            message: 'تم رفض اعتماد المولد.',
            data: new GeneratorResource($rejected)
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Generator::class);

        return $this->success(
            message: 'قائمة المدن المتاحة.',
            data: $this->generatorService->distinctCities($request->user())
        );
    }

    public function mapPoints(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Generator::class);

        return $this->success(
            message: 'نقاط المولدات على الخريطة.',
            data: $this->generatorService->mapPoints($request->user())
        );
    }
}
