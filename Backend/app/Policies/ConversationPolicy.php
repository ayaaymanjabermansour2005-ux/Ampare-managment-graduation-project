<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('conversations.view');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if (! $user->can('conversations.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->hasParticipant($user);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $user->can('conversations.send-message')) {
            return false;
        }

        return $conversation->hasParticipant($user) && ! $conversation->isDeletedFor($user);
    }

    public function convertToIssue(User $user, Conversation $conversation): bool
    {
        if (! $user->can('conversations.send-message')) {
            return false;
        }

        return $conversation->hasParticipant($user) && ! $conversation->isDeletedFor($user);
    }

    public function start(User $user): bool
    {
        return $user->can('conversations.start')
            && ($user->isSubscriber() || $user->isOwner() || $user->isTechnician() || $user->isAdmin());
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->can('conversations.delete') && $conversation->hasParticipant($user);
    }
}
