<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAnnouncement\StoreAdminAnnouncementRequest;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\Policies\AdminDashboardPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class AdminAnnouncementController extends Controller
{
    use ApiResponse;

    public function store(StoreAdminAnnouncementRequest $request, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view($request->user()), 403, __('admin.unauthorized'));

        $validated = $request->validated();

        $usersQuery = User::query();

        if ($validated['audience'] !== 'all') {
            $usersQuery->role($validated['audience']);
        } else {
            $usersQuery->role([
                Role::SUBSCRIBER->value,
                Role::GENERATOR_OWNER->value,
                Role::TECHNICIAN->value,
            ]);
        }

        $recipientsCount = (clone $usersQuery)->count();

        $usersQuery->chunk(200, function ($users) use ($validated) {
            Notification::send(
                $users,
                new AdminAnnouncementNotification($validated['title'], $validated['message'])
            );
        });

        return $this->success(
            message: __('admin.announcement_sent_message'),
            data: ['recipients_count' => $recipientsCount]
        );
    }
}
