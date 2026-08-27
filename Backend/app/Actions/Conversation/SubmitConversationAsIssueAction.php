<?php

namespace App\Actions\Conversation;

use App\Actions\Complaint\CreateComplaintAction;
use App\DTOs\Complaint\CreateComplaintData;
use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Message;
use App\Models\User;
use App\Services\FaultService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SubmitConversationAsIssueAction
{
    public function __construct(
        private readonly FaultService $faultService,
        private readonly CreateComplaintAction $createComplaintAction,
    ) {}

    public function execute(Conversation $conversation, User $actingUser, string $category, ?int $generatorId): Fault|Complaint
    {
        return DB::transaction(function () use ($conversation, $actingUser, $category, $generatorId) {
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);

            if ($conversation->fault()->exists() || $conversation->complaint()->exists()) {
                throw ValidationException::withMessages([
                    'conversation' => ['تم تحويل هذه المحادثة إلى بلاغ عطل أو شكوى مسبقًا.'],
                ]);
            }

            if (! $conversation->messages()->exists()) {
                throw ValidationException::withMessages([
                    'conversation' => ['لا يمكن تحويل محادثة فاضية — تبادلا الرسائل أولًا.'],
                ]);
            }

            $transcript = $this->buildTranscript($conversation);
            $title = $this->buildTitle($conversation);
            $description = $this->buildDescription($transcript);

            if ($category === 'fault') {
                $generator = Generator::findOrFail($generatorId);

                $fault = $this->faultService->create([
                    'generator_id' => $generator->id,
                    'title' => $title,
                    'description' => $description,
                ], $actingUser);

                $fault->forceFill(['conversation_id' => $conversation->id])->save();

                return $fault->fresh(['generator', 'reporter']);
            }

            $otherUser = $conversation->otherParticipant($actingUser);

            $complainableType = $generatorId ? 'generator' : 'user';
            $complainableId = $generatorId ?: $otherUser?->id;

            if (! $complainableId) {
                throw ValidationException::withMessages([
                    'conversation' => ['تعذر تحديد الطرف الآخر في المحادثة لتقديم الشكوى.'],
                ]);
            }

            $complaint = $this->createComplaintAction->execute(
                CreateComplaintData::fromArray([
                    'complainable_type' => $complainableType,
                    'complainable_id' => $complainableId,
                    'subject' => $title,
                    'description' => $description,
                ]),
                $actingUser
            );

            $complaint->forceFill(['conversation_id' => $conversation->id])->save();

            return $complaint->fresh(['submitter', 'complainable']);
        });
    }

    private function buildTranscript(Conversation $conversation): string
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->oldest()
            ->get()
            ->map(fn (Message $m) => sprintf('%s: %s', $m->sender?->name ?? 'مستخدم', $m->message_text))
            ->implode("\n\n");
    }

    private function buildTitle(Conversation $conversation): string
    {
        $firstMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->oldest()
            ->first();

        return $firstMessage
            ? Str::limit($firstMessage->message_text, 100)
            : 'بلاغ محوّل من محادثة';
    }

    private function buildDescription(string $transcript): string
    {
        return Str::limit(
            "محادثة محوّلة من الرسائل:\n\n{$transcript}",
            2000
        );
    }
}
