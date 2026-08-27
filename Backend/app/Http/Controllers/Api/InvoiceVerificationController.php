<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceVerificationController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error(
                message: 'رابط التحقق غير صالح أو منتهي الصلاحية.',
                code: 403
            );
        }

        $invoice->loadMissing('subscription.generator');

        return $this->success(
            message: 'نتيجة التحقق من الفاتورة.',
            data: [
                'invoice_id' => $invoice->id,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'final_amount' => (float) $invoice->final_amount,
                'due_date' => $invoice->due_date?->toDateString(),
                'generator_name' => $invoice->subscription?->generator?->name,
                'issued_at' => $invoice->created_at?->toDateString(),
            ]
        );
    }
}
