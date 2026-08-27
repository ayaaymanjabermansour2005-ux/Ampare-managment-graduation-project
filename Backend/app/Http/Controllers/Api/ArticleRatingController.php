<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleRatingRequest;
use App\Models\Article;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleRatingController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Article $article): JsonResponse
    {
        $visitorHash = $this->visitorHash($request);

        $myRating = $article->ratings()->where('visitor_hash', $visitorHash)->value('rating');

        return $this->success(
            message: 'تقييم المقالة.',
            data: [
                'average' => round((float) $article->ratings()->avg('rating'), 1),
                'count' => $article->ratings()->count(),
                'my_rating' => $myRating,
            ]
        );
    }

    public function store(StoreArticleRatingRequest $request, Article $article): JsonResponse
    {
        $visitorHash = $this->visitorHash($request);

        $article->ratings()->updateOrCreate(
            ['visitor_hash' => $visitorHash],
            ['rating' => $request->validated('rating')]
        );

        return $this->success(
            message: 'شكرًا لتقييمك!',
            data: [
                'average' => round((float) $article->ratings()->avg('rating'), 1),
                'count' => $article->ratings()->count(),
                'my_rating' => (int) $request->validated('rating'),
            ]
        );
    }

    private function visitorHash(Request $request): string
    {
        return hash('sha256', $request->ip().'|'.$request->userAgent());
    }
}
