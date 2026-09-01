<?php

namespace App\Http\Controllers\Api;

use App\Actions\Invoice\CancelInvoiceAction;
use App\Actions\Invoice\CorrectInvoiceAction;
use App\Actions\Invoice\ReissueInvoiceAction;
use App\Exports\InvoicesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CorrectInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\Pdf\InvoicePdfService;
use App\Services\QrCodeService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoiceService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->integer('subscriber_id') ?: null,
            $request->has('status')
                ? array_filter(explode(',', (string) $request->input('status')))
                : null
        );

        return $this->success(
            message: 'قائمة الفواتير.',
            data: InvoiceResource::collection($invoices)->response()->getData(true)
        );
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return $this->success(
            message: 'بيانات الفاتورة.',
            data: new InvoiceResource($invoice->load(
                'subscription.generator',
                'subscription.subscriberMeter.subscriber.user',
                'payments.paymentMethod',
                'commission'
            ))
        );
    }

    public function paymentMethods(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $methods = $this->invoiceService->availablePaymentMethods($invoice);

        return $this->success(
            message: 'وسائل الدفع المتاحة لهذه الفاتورة.',
            data: PaymentMethodResource::collection($methods)
        );
    }

    public function cancel(Invoice $invoice, CancelInvoiceAction $action): JsonResponse
    {
        $this->authorize('cancel', $invoice);

        $invoice = $action->execute($invoice);

        return $this->success(
            message: 'تم إلغاء الفاتورة بنجاح.',
            data: new InvoiceResource($invoice->load('subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'payments.paymentMethod', 'commission'))
        );
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $this->authorize('view', $invoice);

        return $pdfService->download($invoice);
    }

    public function qrCode(Invoice $invoice, QrCodeService $qrCodeService): JsonResponse
    {
        $this->authorize('view', $invoice);

        return $this->success(
            message: 'رمز QR للتحقق من الفاتورة.',
            data: ['qr' => $qrCodeService->invoiceVerificationQrBase64($invoice)]
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Invoice::class);

        return Excel::download(
            new InvoicesExport(
                $request->user(),
                $request->input('from'),
                $request->input('to'),
                $request->input('status')
            ),
            'invoices-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function correct(CorrectInvoiceRequest $request, Invoice $invoice, CorrectInvoiceAction $action): JsonResponse
    {
        $this->authorize('correct', $invoice);

        $invoice = $action->execute(
            $invoice,
            (float) $request->validated('final_amount'),
            $request->validated('reason'),
            $request->user()
        );

        return $this->success(message: 'تم تصحيح الفاتورة.', data: new InvoiceResource($invoice->load(
            'subscription.generator',
            'subscription.subscriberMeter.subscriber.user',
            'payments.paymentMethod',
            'commission'
        )));
    }

    public function reissue(Request $request, Invoice $invoice, ReissueInvoiceAction $action): JsonResponse
    {
        $this->authorize('reissue', $invoice);

        $newInvoice = $action->execute($invoice, $request->user());

        return $this->success(message: 'تم إصدار فاتورة جديدة.', data: new InvoiceResource($newInvoice->load('subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'payments.paymentMethod', 'commission')), code: 201);
    }
}
