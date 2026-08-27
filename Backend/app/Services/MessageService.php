<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(
        protected AttachmentService $attachmentService
    ) {}

    public function listForConversation(Conversation $conversation, int $perPage = 30): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with(['sender', 'attachments'])
            ->oldest()
            ->paginate($perPage);
    }

    public function send(Conversation $conversation, User $sender, ?string $text, array $files = []): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $text, $files) {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'message_text' => $text ?? '',
                'is_read' => false,
            ]);

            if (! empty($files)) {
                $this->attachmentService->uploadMany(
                    model: $message,
                    files: $files,
                    documentType: DocumentType::MessageAttachment->value,
                    user: $sender,
                );
            }

            $conversation->touch();

            $fresh = $message->fresh(['sender', 'attachments']);

            MessageSent::dispatch($fresh);

            return $fresh;
        });
    }

    public function markConversationAsReadFor(Conversation $conversation, User $user): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
