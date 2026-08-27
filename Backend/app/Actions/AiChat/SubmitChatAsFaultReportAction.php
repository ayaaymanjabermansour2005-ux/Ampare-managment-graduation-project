<?php

namespace App\Actions\AiChat;

use App\Enums\AiChatMessageRole;
use App\Models\AiChatSession;
use App\Models\Fault;
use App\Services\FaultService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SubmitChatAsFaultReportAction
{
    public function __construct(private readonly FaultService $faultService) {}

    public function execute(AiChatSession $session): Fault
    {
        return DB::transaction(function () use ($session) {
            $session = AiChatSession::lockForUpdate()->findOrFail($session->id);

            if ($session->fault()->exists()) {
                throw ValidationException::withMessages([
                    'session' => ['تم رفع هذه المحادثة كبلاغ عطل مسبقًا.'],
                ]);
            }

            $session->loadMissing(['messages', 'user']);

            if ($session->messages->isEmpty()) {
                throw ValidationException::withMessages([
                    'session' => ['لا يمكن رفع محادثة فاضية كبلاغ عطل — تحدّثي مع المساعد أولًا.'],
                ]);
            }

            $fault = $this->faultService->create([
                'generator_id' => $session->generator_id,
                'title' => $this->buildTitle($session),
                'description' => $this->buildDescription($session),
            ], $session->user);

            $fault->forceFill(['ai_chat_session_id' => $session->id])->save();

            return $fault->fresh(['generator', 'reporter']);
        });
    }

    private function buildTitle(AiChatSession $session): string
    {
        $firstUserMessage = $session->messages
            ->where('role', AiChatMessageRole::User)
            ->first();

        $summary = $firstUserMessage
            ? Str::limit($firstUserMessage->content, 100)
            : 'بلاغ عطل عبر المساعد الذكي';

        return $summary;
    }

    private function buildDescription(AiChatSession $session): string
    {
        $transcript = $session->messages
            ->map(fn($m) => sprintf(
                '%s: %s',
                $m->role === AiChatMessageRole::User ? 'المشترك' : 'المساعد الذكي',
                $m->content
            ))
            ->implode("\n\n");

        return Str::limit(
            "بلاغ تم رفعه تلقائيًا من محادثة مع المساعد الذكي:\n\n{$transcript}",
            2000
        );
    }
}
