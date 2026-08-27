<?php

namespace App\Http\Controllers\Api;

use App\Actions\Technician\CreateTechnicianUserAction;
use App\DTOs\Technician\CreateTechnicianUserData;
use App\Exports\TechniciansExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Technician\CreateTechnicianAccountRequest;
use App\Http\Requests\Technician\StoreTechnicianAttachmentRequest;
use App\Http\Requests\Technician\StoreTechnicianRequest;
use App\Http\Requests\Technician\UpdateTechnicianRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\TechnicianResource;
use App\Models\Generator;
use App\Models\Technician;
use App\Services\AttachmentService;
use App\Services\TechnicianService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  				الفنيين وأوامر الشغل
 */
class TechnicianController extends Controller
{
    use ApiResponse;

    public function __construct(protected TechnicianService $technicianService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Technician::class);

        $technicians = $this->technicianService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->query('search')
        );

        return $this->success(
            message: 'قائمة الفنيين.',
            data: TechnicianResource::collection($technicians)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Technician::class);

        return Excel::download(
            new TechniciansExport(
                $request->user(),
                $request->query('search')
            ),
            'technicians-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(StoreTechnicianRequest $request): JsonResponse
    {
        $this->authorize('create', Technician::class);

        $technician = $this->technicianService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم ربط الفني بنجاح.',
            data: new TechnicianResource($technician),
            code: 201
        );
    }

    public function createAccount(CreateTechnicianAccountRequest $request, CreateTechnicianUserAction $action): JsonResponse
    {
        $this->authorize('create', Technician::class);

        $technician = $action->execute(
            CreateTechnicianUserData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم إنشاء حساب الفني بنجاح. شاركي بيانات الدخول معه.',
            data: new TechnicianResource($technician),
            code: 201
        );
    }

    public function show(Technician $technician): JsonResponse
    {
        $this->authorize('view', $technician);

        return $this->success(
            message: 'بيانات الفني.',
            data: new TechnicianResource($technician->load(['user', 'owner']))
        );
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician): JsonResponse
    {
        $this->authorize('update', $technician);

        $technician = $this->technicianService->update($technician, $request->validated());

        return $this->success(
            message: 'تم تحديث بيانات الفني.',
            data: new TechnicianResource($technician)
        );
    }

    public function destroy(Technician $technician): JsonResponse
    {
        $this->authorize('delete', $technician);

        $this->technicianService->delete($technician);

        return $this->success(message: 'تم حذف الفني بنجاح.');
    }

    public function availableForGenerator(Generator $generator): JsonResponse
    {
        $this->authorize('view', $generator);

        return $this->success(
            message: 'الفنيون المتاحون لهذا المولد.',
            data: TechnicianResource::collection($this->technicianService->availableForGenerator($generator))
        );
    }

    public function linkGenerator(Technician $technician, Generator $generator): JsonResponse
    {
        $this->authorize('manageGeneratorLink', [$technician, $generator]);

        $this->technicianService->linkToGenerator($technician, $generator);

        return $this->success(message: 'تم ربط الفني بالمولّد بنجاح.');
    }

    public function unlinkGenerator(Technician $technician, Generator $generator): JsonResponse
    {
        $this->authorize('manageGeneratorLink', [$technician, $generator]);

        $this->technicianService->unlinkFromGenerator($technician, $generator);

        return $this->success(message: 'تم إلغاء ربط الفني بالمولّد.');
    }

    public function attachments(Technician $technician): JsonResponse
    {
        $this->authorize('view', $technician);

        return $this->success(
            message: 'مرفقات الفني.',
            data: AttachmentResource::collection(
                $technician->attachments()->with('uploader')->latest()->get()
            )
        );
    }

    public function storeAttachment(
        StoreTechnicianAttachmentRequest $request,
        Technician $technician,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('manageAttachments', $technician);

        $attachment = $attachmentService->upload(
            model: $technician,
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
}
