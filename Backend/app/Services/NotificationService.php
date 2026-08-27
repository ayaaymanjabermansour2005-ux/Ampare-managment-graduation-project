<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function list(User $user, bool $unreadOnly = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = $unreadOnly
            ? $user->unreadNotifications()
            : $user->notifications();

        return $query->latest()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            throw ValidationException::withMessages([
                'notification' => ['الإشعار غير موجود أو لا يخصك.'],
            ]);
        }

        $notification->delete();
    }
}
