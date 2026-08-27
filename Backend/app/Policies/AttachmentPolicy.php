<?php

namespace App\Policies;

use App\Models\AiChatMessage;
use App\Models\Attachment;
use App\Models\Complaint;
use App\Models\Generator;
use App\Models\Message;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Technician;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attachments.view');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->can('attachments.view')
            &&
            (
                (int) $attachment->uploaded_by === (int) $user->id
                ||
                $this->belongsToUserBusiness($user, $attachment)
            );
    }

    public function create(User $user): bool
    {
        return $user->can('attachments.create');
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $attachment->uploaded_by === (int) $user->id) {
            return true;
        }

        return $this->belongsToUserBusiness($user, $attachment);
    }

    public function restore(User $user, Attachment $attachment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return $user->isAdmin();
    }

    private function belongsToUserBusiness(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        return match (true) {
            $attachable instanceof Payment => (int) optional($attachable->invoice?->subscription?->generator)->owner_id === (int) $user->id,
            $attachable instanceof Generator => (int) $attachable->owner_id === (int) $user->id,
            $attachable instanceof Technician => (int) $attachable->owner_id === (int) $user->id || (int) $attachable->user_id === (int) $user->id,
            $attachable instanceof Complaint => (int) $attachable->submitted_by === (int) $user->id
                || (int) $attachable->relatedOwnerId() === (int) $user->id,
            $attachable instanceof MeterReading => (int) optional($attachable->subscription?->generator)->owner_id === (int) $user->id,
            $attachable instanceof AiChatMessage => (int) optional($attachable->session)->user_id === (int) $user->id,
            $attachable instanceof Message => in_array(
                (int) $user->id,
                [optional($attachable->conversation)->user1_id, optional($attachable->conversation)->user2_id],
                true
            ),
            $attachable instanceof \App\Models\Article => (int) $attachable->author_id === (int) $user->id,
            default => false,
        };
    }
}
