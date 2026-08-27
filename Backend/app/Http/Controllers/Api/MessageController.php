<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\MessageService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				المحادثات
 */
class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(protected MessageService $messageService) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $this->messageService->listForConversation(
            $conversation,
            PerPageResolver::resolve($request, default: 30)
        );

        $this->messageService->markConversationAsReadFor($conversation, $request->user());

        return $this->success(
            message: 'رسائل المحادثة.',
            data: MessageResource::collection($messages)
        );
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->messageService->send(
            $conversation,
            $request->user(),
            $request->validated('message_text'),
            $request->file('attachments', [])
        );

        return $this->success(
            message: 'تم إرسال الرسالة.',
            data: new MessageResource($message),
            code: 201
        );
    }
}
