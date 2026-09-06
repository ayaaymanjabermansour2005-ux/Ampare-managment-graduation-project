<?php

namespace App\Http\Controllers\Api;

use App\Actions\Complaint\CreateComplaintAction;
use App\Actions\Complaint\ResolveComplaintAction;
use App\DTOs\Complaint\CreateComplaintData;
use App\DTOs\Complaint\ResolveComplaintData;
use App\Exports\ComplaintsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintAttachmentRequest;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\UpdateComplaintAssignmentRequest;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Services\AttachmentService;
use App\Services\ComplaintService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  				الشكاوى
 */
class ComplaintController extends Controller
{
    use ApiResponse;

    public function __construct(protected ComplaintService $complaintService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = $this->complaintService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->input('status'),
            $request->input('channel'),
            $request->input('priority'),
            $request->integer('assigned_to') ?: null,
        );

        return $this->success(
            message: 'قائمة الشكاوى.',
            data: ComplaintResource::collection($complaints)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Complaint::class);

        return Excel::download(
            new ComplaintsExport(
                $request->user(),
                $request->input('search'),
                $request->input('status'),
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('channel'),
                $request->input('priority'),
                $request->integer('assigned_to') ?: null,
            ),
            'complaints-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(StoreComplaintRequest $request, CreateComplaintAction $action): JsonResponse
    {
        $this->authorize('create', Complaint::class);

        $complaint = $action->execute(
            CreateComplaintData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم تقديم الشكوى بنجاح.',
            data: new ComplaintResource($complaint),
            code: 201
        );
    }

    public function show(Complaint $complaint): JsonResponse
    {
        $this->authorize('view', $complaint);

        $complaint->load(['submitter', 'resolver', 'assignedTo', 'complainable']);

        return $this->success(
            message: 'تفاصيل الشكوى.',
            data: new ComplaintResource($complaint)
        );
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint, ResolveComplaintAction $action): JsonResponse
    {
        $this->authorize('resolve', $complaint);

        $updated = $action->execute(
            $complaint,
            ResolveComplaintData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم تحديث حالة الشكوى.',
            data: new ComplaintResource($updated)
        );
    }

    public function assign(UpdateComplaintAssignmentRequest $request, Complaint $complaint): JsonResponse
    {
        $this->authorize('assign', Complaint::class);

        $updated = $this->complaintService->assign($complaint, $request->validated('assigned_to'));

        return $this->success(
            message: 'تم تحديث المسؤول عن الشكوى.',
            data: new ComplaintResource($updated)
        );
    }

    public function destroy(Complaint $complaint): JsonResponse
    {
        $this->authorize('delete', $complaint);

        $complaint->delete();

        return $this->success(message: 'تم حذف الشكوى بنجاح.');
    }

    public function storeAttachment(
        StoreComplaintAttachmentRequest $request,
        Complaint $complaint,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('manageAttachments', $complaint);

        $attachment = $attachmentService->upload(
            model: $complaint,
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
