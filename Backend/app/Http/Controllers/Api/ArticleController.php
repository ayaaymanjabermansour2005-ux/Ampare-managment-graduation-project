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
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(protected ArticleService $service) {}

    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        return $this->success(
            message: 'المقالات.',
            data: ArticleResource::collection($this->service->listForAdmin())->response()->getData(true)
        );
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->service->create($request->validated(), $request->user());

        return $this->success(message: 'تم إنشاء المقال.', data: new ArticleResource($article), code: 201);
    }

    public function update(StoreArticleRequest $request, Article $article): JsonResponse
    {
        $article = $this->service->update($article, $request->validated());

        return $this->success(message: 'تم تحديث المقال.', data: new ArticleResource($article));
    }

    public function destroy(Article $article): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $this->service->delete($article);

        return $this->success(message: 'تم حذف المقال.');
    }

    public function storeAttachment(
        StoreArticleAttachmentRequest $request,
        Article $article,
        AttachmentService $attachmentService
    ): JsonResponse {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

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
