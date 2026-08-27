<?php

namespace App\Actions\AiChat;

use App\DTOs\AiChat\StartAiChatSessionData;
use App\Enums\AiChatContextType;
use App\Models\AiChatSession;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class StartAiChatSessionAction
{
    public function __construct(
        private readonly SendAiChatMessageAction $sendMessageAction
    ) {}

    public function execute(StartAiChatSessionData $data, User $user, ?UploadedFile $file = null): AiChatSession
    {
        $generator = $data->generatorId ? Generator::findOrFail($data->generatorId) : null;

        // Defense-in-depth: StoreAiChatSessionRequest::withValidator already checks
        // $user->can('view', $generator), but this Action is the only place a session
        // is actually created — re-asserting ownership here means a future caller of
        // this Action can never bypass the check by skipping the FormRequest.
        if (! $generator && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'generator_id' => 'يجب تحديد المولد المراد الاستفسار عنه.',
            ]);
        }

        if ($generator) {
            Gate::forUser($user)->authorize('view', $generator);
        }

        $session = AiChatSession::create([
            'user_id' => $user->id,
            'generator_id' => $generator?->id,
            'context_type' => $this->resolveContextType($user),
        ]);

        if ($data->initialMessage || $file) {
            $this->sendMessageAction->execute($session, $data->initialMessage ?? '', $file);
        }

        return $session->fresh(['generator', 'messages.attachments']);
    }

    private function resolveContextType(User $user): string
    {
        return $user->isSubscriber()
            ? AiChatContextType::SubscriberSupport->value
            : AiChatContextType::OwnerDiagnostic->value;
    }
}
