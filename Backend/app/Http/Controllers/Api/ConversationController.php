<?php

namespace App\Http\Controllers\Api;

use App\Actions\Conversation\SubmitConversationAsIssueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ConvertConversationToIssueRequest;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Resources\ComplaintResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\FaultResource;
use App\Models\Conversation;
use App\Models\Fault;
use App\Models\User;
use App\Services\ConversationService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				المحادثات
 */
class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(protected ConversationService $conversationService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $conversations = $this->conversationService->list(
            $request->user(),
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة المحادثات.',
            data: ConversationResource::collection($conversations)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        return $this->success(
            message: 'عدد الرسائل غير المقروءة.',
            data: ['unread_count' => $this->conversationService->unreadMessagesCount($request->user())]
        );
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $this->authorize('start', Conversation::class);

        $targetUser = User::findOrFail($request->validated('user_id'));

        $conversation = $this->conversationService->findOrCreateDirect($request->user(), $targetUser);

        return $this->success(
            message: 'تم فتح المحادثة بنجاح.',
            data: new ConversationResource($conversation),
            code: 201
        );
    }

    public function startSupport(Request $request): JsonResponse
    {
        $this->authorize('start', Conversation::class);

        $supportAgent = User::role('admin')->oldest()->first();

        if (! $supportAgent) {
            return $this->error(message: 'لا يوجد حساب دعم فني متاح حاليًا.', code: 404);
        }

        $conversation = $this->conversationService->findOrCreateDirect($request->user(), $supportAgent);

        return $this->success(
            message: 'تم فتح محادثة الدعم الفني بنجاح.',
            data: new ConversationResource($conversation),
            code: 201
        );
    }

    /**
     * فتح محادثة الفني مع المالك الذي يتبع له — الهدف يُحدَّد بالكامل من
     * علاقة الفني بالمالك في قاعدة البيانات، ولا يُقبل أي معرّف من الطلب.
     */
    public function startWithOwner(Request $request): JsonResponse
    {
        $this->authorize('start', Conversation::class);

        $user = $request->user();

        if (! $user->isTechnician()) {
            return $this->error(message: 'هذا الإجراء متاح للفنيين فقط.', code: 403);
        }

        $owner = $user->technician?->owner;

        if (! $owner) {
            return $this->error(message: 'لا يوجد مالك مرتبط بحسابك حاليًا.', code: 404);
        }

        $conversation = $this->conversationService->findOrCreateDirect($user, $owner);

        return $this->success(
            message: 'تم فتح المحادثة مع المالك بنجاح.',
            data: new ConversationResource($conversation),
            code: 201
        );
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load('user1', 'user2');

        return $this->success(
            message: 'تفاصيل المحادثة.',
            data: new ConversationResource($conversation)
        );
    }

    public function destroy(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('delete', $conversation);

        $this->conversationService->deleteForUser($conversation, $request->user());

        return $this->success(message: 'تم حذف المحادثة من قائمتك.');
    }

    public function convertToIssue(
        ConvertConversationToIssueRequest $request,
        Conversation $conversation,
        SubmitConversationAsIssueAction $action
    ): JsonResponse {
        $this->authorize('convertToIssue', $conversation);

        $result = $action->execute(
            $conversation,
            $request->user(),
            $request->validated('category'),
            $request->validated('generator_id')
        );

        return $this->success(
            message: $result instanceof Fault ? 'تم تحويل المحادثة إلى بلاغ عطل بنجاح.' : 'تم تحويل المحادثة إلى شكوى بنجاح.',
            data: $result instanceof Fault ? new FaultResource($result) : new ComplaintResource($result),
            code: 201
        );
    }
}
