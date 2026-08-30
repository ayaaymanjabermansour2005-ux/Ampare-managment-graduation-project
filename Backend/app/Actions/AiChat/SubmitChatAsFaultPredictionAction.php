<?php

namespace App\Actions\AiChat;

use App\Enums\AiChatMessageRole;
use App\Enums\FaultPredictionSource;
use App\Enums\FaultPredictionStatus;
use App\Models\AiChatSession;
use App\Models\FaultPrediction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitChatAsFaultPredictionAction
{
    public function execute(AiChatSession $session): FaultPrediction
    {
        return DB::transaction(function () use ($session) {
            $session = AiChatSession::lockForUpdate()->findOrFail($session->id);

            if ($session->faultPrediction()->exists()) {
                throw ValidationException::withMessages([
                    'session' => ['تم رفع هذه المحادثة للمراجعة مسبقًا.'],
                ]);
            }

            $session->loadMissing('messages');

            if ($session->messages->isEmpty()) {
                throw ValidationException::withMessages([
                    'session' => ['لا يمكن رفع محادثة فاضية للمراجعة — تحدّثي مع المساعد أولًا.'],
                ]);
            }

            $lastAssistantMessage = $session->messages
                ->where('role', AiChatMessageRole::Assistant)
                ->last();

            return FaultPrediction::create([
                'generator_id' => $session->generator_id,
                'source' => FaultPredictionSource::Chat,
                'ai_chat_session_id' => $session->id,
                'prediction_type' => 'chat_diagnosis',
                'recommendation' => $lastAssistantMessage?->content
                    ?? 'تم رفع المحادثة للمراجعة دون رد واضح من المساعد الذكي.',
                'input_snapshot' => $session->messages
                    ->map(fn ($m) => ['role' => $m->role->value, 'content' => $m->content])
                    ->all(),
                'is_actual_fault' => false,
                'status' => FaultPredictionStatus::Pending->value,
            ]);
        });
    }
}
