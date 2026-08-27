<?php

namespace App\Http\Controllers\Api;

use App\Enums\ContactMessageStatus;
use App\Events\ContactMessageReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessage\StoreContactMessageRequest;
use App\Http\Requests\ContactMessage\UpdateContactMessageStatusRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class ContactMessageController extends Controller
{
    use ApiResponse;

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        ContactMessageReceived::dispatch($message);

        return $this->success(
            message: 'تم استلام رسالتك بنجاح، رح نرد عليك قريبًا.',
            code: 201
        );
    }

    public function index(Request $request, ContactMessageService $service): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $messages = $service->list(
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->input('status')
        );

        return $this->success(
            message: 'قائمة رسائل التواصل.',
            data: ContactMessageResource::collection($messages)->response()->getData(true)
        );
    }

    public function show(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        return $this->success(
            message: 'تفاصيل الرسالة.',
            data: new ContactMessageResource($contactMessage->load('handledBy'))
        );
    }

    public function updateStatus(
        UpdateContactMessageStatusRequest $request,
        ContactMessage $contactMessage,
        ContactMessageService $service
    ): JsonResponse {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $updated = $service->updateStatus(
            $contactMessage,
            ContactMessageStatus::from($request->validated('status')),
            $request->validated('admin_note'),
            $request->user()
        );

        return $this->success(
            message: 'تم تحديث حالة الرسالة.',
            data: new ContactMessageResource($updated)
        );
    }
}
