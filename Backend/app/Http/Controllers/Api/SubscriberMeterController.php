<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriberMeter\StoreSubscriberMeterRequest;
use App\Http\Requests\SubscriberMeter\UpdateSubscriberMeterRequest;
use App\Http\Resources\SubscriberMeterResource;
use App\Models\SubscriberMeter;
use App\Services\QrCodeService;
use App\Services\SubscriberMeterService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberMeterController extends Controller
{
    use ApiResponse;

    public function __construct(protected SubscriberMeterService $subscriberMeterService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriberMeter::class);

        $meters = $this->subscriberMeterService->list($request->user());

        return $this->success(
            message: 'عداداتك المسجّلة.',
            data: SubscriberMeterResource::collection($meters)
        );
    }

    public function store(StoreSubscriberMeterRequest $request): JsonResponse
    {
        $this->authorize('create', SubscriberMeter::class);

        $meter = $this->subscriberMeterService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم إضافة العداد بنجاح.',
            data: new SubscriberMeterResource($meter),
            code: 201
        );
    }

    public function show(SubscriberMeter $subscriberMeter): JsonResponse
    {
        $this->authorize('view', $subscriberMeter);

        // FIX: SubscriberMeterResource بيصدّر subscriptions_count عبر
        // whenCounted، بس ما حدا كان عم يعمل loadCount() — لا هون ولا بـ
        // list() — فالقيمة كانت دايمًا مفقودة وصفحة SubscriberMeterDetailView
        // ما كانت تعرض عدد العقود أبداً رغم إنها مبنية لهيك.
        $subscriberMeter->loadCount('subscriptions');

        return $this->success(
            message: 'بيانات العداد.',
            data: new SubscriberMeterResource($subscriberMeter)
        );
    }

    public function update(UpdateSubscriberMeterRequest $request, SubscriberMeter $subscriberMeter): JsonResponse
    {
        $this->authorize('update', $subscriberMeter);

        $meter = $this->subscriberMeterService->update($subscriberMeter, $request->validated());

        return $this->success(
            message: 'تم تحديث بيانات العداد بنجاح.',
            data: new SubscriberMeterResource($meter)
        );
    }

    public function destroy(SubscriberMeter $subscriberMeter): JsonResponse
    {
        $this->authorize('delete', $subscriberMeter);

        $this->subscriberMeterService->delete($subscriberMeter);

        return $this->success(message: 'تم حذف العداد بنجاح.');
    }

    public function qrCode(SubscriberMeter $subscriberMeter, QrCodeService $qrCodeService): JsonResponse
    {
        $this->authorize('view', $subscriberMeter);

        return $this->success(
            message: 'رمز QR للعداد.',
            data: ['qr' => $qrCodeService->subscriberMeterQrBase64($subscriberMeter)]
        );
    }
}
