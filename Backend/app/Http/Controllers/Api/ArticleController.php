<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleAttachmentRequest;
use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\AttachmentResource;
use App\Models\Article;
use App\Services\ArticleService;
use App\Services\AttachmentService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(protected ArticleService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        return $this->success(
            message: 'المقالات.',
            // FIX (تدقيق شامل — D8): per_page لم يكن قابلًا للتخصيص إطلاقًا،
            // خلافًا لكل نقاط النهاية الأخرى المشابهة.
            data: ArticleResource::collection($this->service->listForAdmin(PerPageResolver::resolve($request)))->response()->getData(true)
        );
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $this->authorize('create', Article::class);

        $article = $this->service->create($request->validated(), $request->user());

        return $this->success(message: 'تم إنشاء المقال.', data: new ArticleResource($article), code: 201);
    }

    public function update(StoreArticleRequest $request, Article $article): JsonResponse
    {
        $this->authorize('update', $article);

        $article = $this->service->update($article, $request->validated());

        return $this->success(message: 'تم تحديث المقال.', data: new ArticleResource($article));
    }

    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        $this->service->delete($article);

        return $this->success(message: 'تم حذف المقال.');
    }

    public function storeAttachment(
        StoreArticleAttachmentRequest $request,
        Article $article,
        AttachmentService $attachmentService
    ): JsonResponse {
        $this->authorize('update', $article);

        $attachment = $attachmentService->upload(
            model: $article,
            file: $request->file('file'),
            documentType: DocumentType::ArticleImage->value,
            user: $request->user(),
        );

        return $this->success(
            message: 'تمت إضافة الصورة للمقال بنجاح.',
            data: new AttachmentResource($attachment->load('uploader')),
            code: 201
        );
    }

    public function publicIndex(): JsonResponse
    {
        return $this->success(
            message: 'المقالات المنشورة.',
            data: ArticleResource::collection($this->service->listPublished())->response()->getData(true)
        );
    }

    public function publicShow(string $slug): JsonResponse
    {
        $article = $this->service->showPublished($slug);

        return $this->success(message: 'تفاصيل المقال.', data: new ArticleResource($article->load('author')));
    }
}
