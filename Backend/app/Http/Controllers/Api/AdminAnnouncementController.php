<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\Policies\AdminDashboardPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class AdminAnnouncementController extends Controller
{
    use ApiResponse;

    public function store(Request $request, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view($request->user()), 403, __('admin.unauthorized'));

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', Rule::in(['all', 'subscriber', 'generator_owner', 'technician'])],
        ]);

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
