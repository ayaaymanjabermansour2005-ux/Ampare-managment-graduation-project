<?php

namespace App\Policies;

use App\Models\AiChatSession;
use App\Models\User;

class AiChatSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ai-chat.use');
    }

    public function view(User $user, AiChatSession $session): bool
    {
        if (! $user->can('ai-chat.use')) {
            return false;
        }

        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('ai-chat.use');
    }

    public function submitAsPrediction(User $user, AiChatSession $session): bool
    {
        return $user->can('ai-chat.use')
            && $session->user_id === $user->id
            && $session->isOwnerDiagnostic();
    }

    public function submitAsFaultReport(User $user, AiChatSession $session): bool
    {
        return $user->can('ai-chat.use')
            && $session->user_id === $user->id
            && $session->isSubscriberSupport();
    }
}
