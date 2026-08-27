<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleCommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Article\ReplyArticleCommentRequest;
use App\Http\Requests\Article\StoreArticleCommentRequest;
use App\Http\Resources\ArticleCommentResource;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleCommentController extends Controller
{
    use ApiResponse;

    public function index(Article $article): JsonResponse
    {
        $comments = $article->approvedComments()->paginate(20);

        return $this->success(
            message: 'قائمة التعليقات.',
            data: ArticleCommentResource::collection($comments)->response()->getData(true)
        );
    }

    public function store(StoreArticleCommentRequest $request, Article $article): JsonResponse
    {
        $comment = $article->comments()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'comment' => $request->validated('comment'),
            'status' => ArticleCommentStatus::Pending->value,
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            message: 'تم إرسال تعليقك، سيظهر بعد مراجعته من فريقنا.',
            data: new ArticleCommentResource($comment),
            code: 201
        );
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $status = $request->input('status', ArticleCommentStatus::Pending->value);

        $comments = ArticleComment::query()
            ->with(['article:id,title,slug', 'reviewer:id,name', 'replier:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(PerPageResolver::resolve($request));

        return $this->success(
            message: 'قائمة التعليقات (إدارة).',
            data: ArticleCommentResource::collection($comments)->response()->getData(true)
        );
    }

    public function approve(Request $request, ArticleComment $comment): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $comment->update([
            'status' => ArticleCommentStatus::Approved->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->success(
            message: 'تم اعتماد التعليق.',
            data: new ArticleCommentResource($comment->fresh(['article', 'reviewer']))
        );
    }

    public function reject(Request $request, ArticleComment $comment): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $comment->update([
            'status' => ArticleCommentStatus::Rejected->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->success(
            message: 'تم رفض التعليق.',
            data: new ArticleCommentResource($comment->fresh(['article', 'reviewer']))
        );
    }

    public function destroy(Request $request, ArticleComment $comment): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $comment->delete();

        return $this->success(message: 'تم حذف التعليق نهائيًا.');
    }

    public function reply(ReplyArticleCommentRequest $request, ArticleComment $comment): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $comment->update([
            'admin_reply' => $request->validated('admin_reply'),
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
        ]);

        return $this->success(
            message: 'تم حفظ الرد.',
            data: new ArticleCommentResource($comment->fresh(['article', 'reviewer', 'replier']))
        );
    }

    /**
     * حذف رد الأدمن فقط (بدون حذف التعليق الأصلي نفسه).
     */
    public function deleteReply(Request $request, ArticleComment $comment): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $comment->update([
            'admin_reply' => null,
            'replied_by' => null,
            'replied_at' => null,
        ]);

        return $this->success(
            message: 'تم حذف الرد.',
            data: new ArticleCommentResource($comment->fresh(['article', 'reviewer', 'replier']))
        );
    }
}
