<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicAnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class PublicAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(protected PublicAnalyticsService $analyticsService) {}

    public function index(): JsonResponse
    {
        $data = Cache::remember('public_platform_analytics_v3', now()->addMinutes(30), function () {
            return $this->analyticsService->summary();
        });

        return $this->success(
            message: 'تحليلات المنصة العامة المجمّعة.',
            data: $data
        );
    }
}
