<?php

namespace App\Http\Controllers\Api;

use App\Actions\TechnicianPayment\ApproveTechnicianPaymentAction;
use App\Actions\TechnicianPayment\CreateTechnicianPaymentAction;
use App\Actions\TechnicianPayment\RejectTechnicianPaymentAction;
use App\DTOs\TechnicianPayment\CreateTechnicianPaymentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianPayment\RejectTechnicianPaymentRequest;
use App\Http\Requests\TechnicianPayment\StoreTechnicianPaymentAttachmentRequest;
use App\Http\Requests\TechnicianPayment\StoreTechnicianPaymentRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\TechnicianPaymentResource;
use App\Models\TechnicianPayment;
use App\Services\AttachmentService;
use App\Services\TechnicianPaymentService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				دفعات الفنيين
 */
class TechnicianPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(protected TechnicianPaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TechnicianPayment::class);

        $payments = $this->paymentService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->integer('technician_id') ?: null,
            $request->string('status')->toString() ?: null
        );

        return $this->success(
            message: 'قائمة دفعات الفنيين.',
            data: TechnicianPaymentResource::collection($payments)->response()->getData(true)
        );
    }

    public function store(StoreTechnicianPaymentRequest $request, CreateTechnicianPaymentAction $action): JsonResponse
    {
        $this->authorize('create', TechnicianPayment::class);

        $payment = $action->execute(
            CreateTechnicianPaymentData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم تسجيل الدفعة بنجاح، بانتظار تأكيد الفني.',
            data: new TechnicianPaymentResource($payment),
            code: 201
        );
    }

    public function show(TechnicianPayment $technicianPayment): JsonResponse
    {
        $this->authorize('view', $technicianPayment);

        return $this->success(
            message: 'بيانات الدفعة.',
            data: new TechnicianPaymentResource(
                $technicianPayment->load(['technician.user', 'owner', 'paymentMethod', 'reviewedBy', 'attachments.uploader'])
            )
        );
    }

    public function approve(TechnicianPayment $technicianPayment, ApproveTechnicianPaymentAction $action): JsonResponse
    {
        $this->authorize('approve', $technicianPayment);

        $payment = $action->execute($technicianPayment, request()->user());

        return $this->success(message: 'تم تأكيد استلام الدفعة.', data: new TechnicianPaymentResource($payment));
    }

    public function reject(
        RejectTechnicianPaymentRequest $request,
        TechnicianPayment $technicianPayment,
        RejectTechnicianPaymentAction $action
    ): JsonResponse {
        $this->authorize('reject', $technicianPayment);

        $payment = $action->execute($technicianPayment, $request->user(), $request->validated('reason'));

        return $this->success(message: 'تم رفض الدفعة.', data: new TechnicianPaymentResource($payment));
    }

    public function attachments(TechnicianPayment $technicianPayment): JsonResponse
    {
        $this->authorize('view', $technicianPayment);

        return $this->success(
            message: 'مرفقات الدفعة.',
            data: AttachmentResource::collection(
                $technicianPayment->attachments()->with('uploader')->latest()->get()
            )
        );
    }

    public function storeAttachment(
        StoreTechnicianPaymentAttachmentRequest $request,
        TechnicianPayment $technicianPayment,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('manageAttachments', $technicianPayment);

        $attachment = $attachmentService->upload(
            model: $technicianPayment,
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
