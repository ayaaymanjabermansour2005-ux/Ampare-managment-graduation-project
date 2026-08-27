<?php

use App\Http\Controllers\Api\ArticleCommentController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ArticleRatingController;
use App\Http\Controllers\Api\InvoiceVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('invoices/{invoice}/verify', [InvoiceVerificationController::class, 'show'])
    ->middleware('signed')
    ->name('invoices.verify');

Route::prefix('v1')->group(base_path('routes/api/v1.php'));

Route::get('articles', [ArticleController::class, 'publicIndex'])
    ->name('articles.public-index');

Route::get('articles/{slug}', [ArticleController::class, 'publicShow'])
    ->name('articles.public-show');

/*
|--------------------------------------------------------------------------
| تعليقات وتقييمات المقالات — عامة، بدون تسجيل دخول
|--------------------------------------------------------------------------
*/
Route::get('articles/{article:slug}/comments', [ArticleCommentController::class, 'index'])
    ->name('articles.comments.index');

Route::post('articles/{article:slug}/comments', [ArticleCommentController::class, 'store'])
    ->middleware('throttle:5,1,article-comments')
    ->name('articles.comments.store');

Route::get('articles/{article:slug}/rating', [ArticleRatingController::class, 'show'])
    ->name('articles.rating.show');

Route::post('articles/{article:slug}/rating', [ArticleRatingController::class, 'store'])
    ->middleware('throttle:10,1,article-rating')
    ->name('articles.rating.store');
