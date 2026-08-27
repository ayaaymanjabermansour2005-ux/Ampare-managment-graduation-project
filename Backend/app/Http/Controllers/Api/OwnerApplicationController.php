<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\ApproveOwnerApplicationAction;
use App\Actions\Auth\BulkApproveOwnerApplicationsAction;
use App\Actions\Auth\BulkRejectOwnerApplicationsAction;
use App\Actions\Auth\RejectOwnerApplicationAction;
use App\Actions\Auth\SubmitOwnerApplicationAction;
use App\DTOs\Auth\SubmitOwnerApplicationData;
use App\Exports\OwnerApplicationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BulkApproveOwnerApplicationsRequest;
use App\Http\Requests\Auth\BulkRejectOwnerApplicationsRequest;
use App\Http\Requests\Auth\RejectOwnerApplicationRequest;
use App\Http\Requests\Auth\StoreOwnerApplicationRequest;
use App\Http\Requests\Auth\UpdateOwnerApplicationInternalNoteRequest;
use App\Http\Resources\OwnerApplicationResource;
use App\Models\OwnerApplication;
use App\Models\User;
use App\Services\OwnerApplicationService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  	طلبات انضمام أصحاب المولدات
 */
class OwnerApplicationController extends Controller
{
    use ApiResponse;

    public function __construct(protected OwnerApplicationService $service) {}

    public function store(StoreOwnerApplicationRequest $request, SubmitOwnerApplicationAction $action): JsonResponse
    {
        $validated = $request->validated();

        $validated['documents'] = array_filter([
            'id_document' => $request->file('id_document'),
            'business_license' => $request->file('business_license'),
            'generator_photo' => $request->file('generator_photo'),
            'ownership_contract' => $request->file('ownership_contract'),
        ]);

        $application = $action->execute(
            SubmitOwnerApplicationData::fromArray($validated)
        );

        return $this->success(
            message: 'تم استلام طلب انضمامك بنجاح، سيتم مراجعته والتواصل معك عبر البريد الإلكتروني والواتساب.',
            data: new OwnerApplicationResource($application),
            code: 201
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OwnerApplication::class);

        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $applications = $this->service->list(
            perPage: PerPageResolver::resolve($request),
            status: $request->input('status'),
            search: $request->input('search'),
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date'),
            sort: $request->input('sort', 'created_desc'),
        );

        $responseData = OwnerApplicationResource::collection($applications)->response()->getData(true);
        $responseData['meta']['status_counts'] = $this->service->statusCounts();

        return $this->success(
            message: 'قائمة طلبات انضمام أصحاب المولدات.',
            data: $responseData
        );
    }

    public function show(OwnerApplication $ownerApplication): JsonResponse
    {
        $this->authorize('view', $ownerApplication);

        return $this->success(
            message: 'بيانات طلب الانضمام.',
            data: new OwnerApplicationResource($ownerApplication->load(['reviewedBy', 'createdUser', 'attachments', 'generatorNeighborhood']))
        );
    }

    public function approve(OwnerApplication $ownerApplication, ApproveOwnerApplicationAction $action, Request $request): JsonResponse
    {
        $this->authorize('review', $ownerApplication);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $application = $action->execute($ownerApplication, $user);

        return $this->success(
            message: 'تم قبول الطلب وإنشاء حساب صاحب المولد ومولده (قيد المراجعة الفنية)، وتم إشعاره بالبريد الإلكتروني والواتساب.',
            data: new OwnerApplicationResource($application)
        );
    }

    public function reject(
        RejectOwnerApplicationRequest $request,
        OwnerApplication $ownerApplication,
        RejectOwnerApplicationAction $action
    ): JsonResponse {
        $this->authorize('review', $ownerApplication);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $application = $action->execute(
            $ownerApplication,
            $user,
            $request->validated('reason')
        );

        return $this->success(
            message: 'تم رفض الطلب وإشعار المتقدّم بالبريد الإلكتروني والواتساب.',
            data: new OwnerApplicationResource($application)
        );
    }

    public function updateInternalNote(
        UpdateOwnerApplicationInternalNoteRequest $request,
        OwnerApplication $ownerApplication
    ): JsonResponse {
        $this->authorize('updateInternalNote', $ownerApplication);

        $ownerApplication->update([
            'internal_note' => $request->validated('internal_note'),
        ]);

        return $this->success(
            message: 'تم حفظ الملاحظة الداخلية.',
            data: new OwnerApplicationResource($ownerApplication->fresh(['reviewedBy', 'attachments']))
        );
    }

    public function bulkApprove(BulkApproveOwnerApplicationsRequest $request, BulkApproveOwnerApplicationsAction $action): JsonResponse
    {
        $this->authorize('viewAny', OwnerApplication::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $result = $action->execute($request->validated('application_ids'), $user);

        $approvedCount = count($result['approved']);
        $failedCount = count($result['failed']);

        return $this->success(
            message: $failedCount === 0
                ? "تم قبول {$approvedCount} طلب بنجاح."
                : "تم قبول {$approvedCount} طلب، وتعذّر قبول {$failedCount} طلب.",
            data: $result
        );
    }

    public function bulkReject(BulkRejectOwnerApplicationsRequest $request, BulkRejectOwnerApplicationsAction $action): JsonResponse
    {
        $this->authorize('viewAny', OwnerApplication::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $result = $action->execute(
            $request->validated('application_ids'),
            $user,
            $request->validated('reason')
        );

        $rejectedCount = count($result['rejected']);
        $failedCount = count($result['failed']);

        return $this->success(
            message: $failedCount === 0
                ? "تم رفض {$rejectedCount} طلب بنجاح."
                : "تم رفض {$rejectedCount} طلب، وتعذّر رفض {$failedCount} طلب.",
            data: $result
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', OwnerApplication::class);

        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        return Excel::download(
            new OwnerApplicationsExport(
                $request->input('search'),
                $request->input('status'),
                $request->input('sort'),
                $request->input('from_date'),
                $request->input('to_date'),
            ),
            'owner-applications-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
