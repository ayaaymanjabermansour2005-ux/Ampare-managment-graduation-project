<?php

namespace App\Services;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public function list(int $perPage, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = ContactMessage::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function updateStatus(ContactMessage $message, ContactMessageStatus $status, ?string $adminNote, User $admin): ContactMessage
    {
        $message->update([
            'status' => $status->value,
            'admin_note' => $adminNote,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);

        return $message->fresh('handledBy');
    }
}
