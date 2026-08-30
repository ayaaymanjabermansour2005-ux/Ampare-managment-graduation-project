<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class ConversationService
{
    public function findOrCreateDirect(User $userA, User $userB): Conversation
    {
        if ($userA->id === $userB->id) {
            throw new \InvalidArgumentException('لا يمكن إنشاء محادثة مع النفس.');
        }

        [$user1Id, $user2Id] = $userA->id < $userB->id
            ? [$userA->id, $userB->id]
            : [$userB->id, $userA->id];

        $existing = Conversation::where('user1_id', $user1Id)
            ->where('user2_id', $user2Id)
            ->first();

        if ($existing) {
            $this->undoDeletionIfNeeded($existing, $userA);
            $this->undoDeletionIfNeeded($existing, $userB);

            return $existing->fresh(['user1', 'user2']);
        }

        try {
            $conversation = Conversation::create([
                'user1_id' => $user1Id,
                'user2_id' => $user2Id,
            ]);
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'user1_id')) {
                throw $e;
            }

            $conversation = Conversation::where('user1_id', $user1Id)
                ->where('user2_id', $user2Id)
                ->firstOrFail();
        }

        return $conversation->fresh(['user1', 'user2']);
    }

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $unreadCount = fn ($q) => $q->where('sender_id', '!=', $user->id)->where('is_read', false);

        $query = Conversation::query()
            ->with(['user1', 'user2', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => $unreadCount]);

        if (! $user->isAdmin()) {
            $query->visibleTo($user);
        }

        return $query->latest('updated_at')->paginate($perPage);
    }

    public function unreadMessagesCount(User $user): int
    {
        return Message::query()
            ->whereHas('conversation', fn ($q) => $q->visibleTo($user))
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function deleteForUser(Conversation $conversation, User $user): void
    {
        if ($conversation->user1_id === $user->id) {
            $conversation->update(['user1_deleted_at' => now()]);

            return;
        }

        if ($conversation->user2_id === $user->id) {
            $conversation->update(['user2_deleted_at' => now()]);
        }
    }

    private function undoDeletionIfNeeded(Conversation $conversation, User $user): void
    {
        if ($conversation->user1_id === $user->id && $conversation->user1_deleted_at !== null) {
            $conversation->update(['user1_deleted_at' => null]);
        }

        if ($conversation->user2_id === $user->id && $conversation->user2_deleted_at !== null) {
            $conversation->update(['user2_deleted_at' => null]);
        }
    }
}
