<?php

namespace App\Http\Controllers\Api;

use App\Actions\Payment\ApprovePaymentAction;
use App\Actions\Payment\CancelPaymentAction;
use App\Actions\Payment\CreatePaymentAction;
use App\Actions\Payment\ProcessGatewayPaymentAction;
use App\Actions\Payment\RejectPaymentAction;
use App\Actions\Payment\RequestPaymentCorrectionAction;
use App\Actions\Payment\ResubmitPaymentAction;
use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Payment\ProcessGatewayPaymentData;
use App\DTOs\Payment\ResubmitPaymentData;
use App\Enums\PaymentSource;
use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\NeedsCorrectionPaymentRequest;
use App\Http\Requests\Payment\ProcessGatewayPaymentRequest;
use App\Http\Requests\Payment\RejectPaymentRequest;
use App\Http\Requests\Payment\ResubmitPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  	الدفعات ووسائل الدفع
 */
class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->paymentService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('status')
        );

        return $this->success(
            message: 'قائمة الدفعات.',
            data: PaymentResource::collection($payments)->response()->getData(true)
        );
    }

    public function store(StorePaymentRequest $request, CreatePaymentAction $action): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $action->execute(
            CreatePaymentData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: $payment->source === PaymentSource::Adjustment
                ? 'تم تسجيل التعديل الإداري واعتماده بنجاح.'
                : 'تم إرسال الدفعة بنجاح، بانتظار مراجعة الأونر/الأدمن.',
            data: new PaymentResource($payment),
            code: 201
        );
    }

    public function storeViaGateway(
        ProcessGatewayPaymentRequest $request,
        ProcessGatewayPaymentAction $action
    ): JsonResponse {
        $this->authorize('create', Payment::class);

        $payment = $action->execute(
            ProcessGatewayPaymentData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم الدفع بنجاح عبر بوابة الدفع الإلكتروني.',
            data: new PaymentResource($payment),
            code: 201
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return $this->success(
            message: 'بيانات الدفعة.',
            data: new PaymentResource(
                $payment->load(['paymentMethod', 'attachments.uploader', 'processedBy', 'reviewedBy', 'reviews.reviewer'])
            )
        );
    }

    public function approve(Request $request, Payment $payment, ApprovePaymentAction $action): JsonResponse
    {
        $this->authorize('approve', $payment);

        $payment = $action->execute($payment, $request->user());

        return $this->success(
            message: 'تم اعتماد الدفعة بنجاح.',
            data: new PaymentResource($payment)
        );
    }

    public function reject(RejectPaymentRequest $request, Payment $payment, RejectPaymentAction $action): JsonResponse
    {
        $this->authorize('reject', $payment);

        $payment = $action->execute(
            $payment,
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->success(
            message: 'تم رفض الدفعة بنجاح.',
            data: new PaymentResource($payment)
        );
    }

    public function needsCorrection(
        NeedsCorrectionPaymentRequest $request,
        Payment $payment,
        RequestPaymentCorrectionAction $action
    ): JsonResponse {
        $this->authorize('needsCorrection', $payment);

        $payment = $action->execute(
            $payment,
            $request->user(),
            $request->validated('note')
        );

        return $this->success(
            message: 'تم إرجاع الدفعة للمشترك لطلب تعديل.',
            data: new PaymentResource($payment)
        );
    }

    public function resubmit(ResubmitPaymentRequest $request, Payment $payment, ResubmitPaymentAction $action): JsonResponse
    {
        $this->authorize('resubmit', $payment);

        $payment = $action->execute(
            $payment,
            ResubmitPaymentData::fromArray($request->validated()),
            $request->user()
        );

        return $this->success(
            message: 'تم إعادة إرسال الدفعة بنجاح، بانتظار المراجعة.',
            data: new PaymentResource($payment)
        );
    }

    public function cancel(Payment $payment, CancelPaymentAction $action): JsonResponse
    {
        $this->authorize('cancel', $payment);

        $payment = $action->execute($payment);

        return $this->success(
            message: 'تم إلغاء الدفعة بنجاح.',
            data: new PaymentResource($payment)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Payment::class);

        return Excel::download(
            new PaymentsExport(
                $request->user(),
                $request->input('from'),
                $request->input('to'),
                $request->input('status')
            ),
            'payments-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
