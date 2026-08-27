<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				الإشعارات
 */
class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = $request->boolean('unread_only');

        $notifications = $this->notificationService->list(
            $request->user(),
            $unreadOnly,
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة الإشعارات.',
            data: NotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(
            message: 'عدد الإشعارات غير المقروءة.',
            data: ['unread_count' => $this->notificationService->unreadCount($request->user())]
        );
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $result = $this->notificationService->markAsRead($request->user(), $notification);

        return $this->success(
            message: 'تم تعليم الإشعار كمقروء.',
            data: new NotificationResource($result)
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return $this->success(
            message: "تم تعليم {$count} إشعار كمقروء."
        );
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->delete($request->user(), $notification);

        return $this->success(message: 'تم حذف الإشعار بنجاح.');
    }
}
