<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SidebarBadgeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SidebarController extends Controller
{
    use ApiResponse;

    public function badgeCounts(Request $request, SidebarBadgeService $service): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $counts = Cache::remember('admin.sidebar_badge_counts', now()->addMinute(), fn () => $service->counts());

        return $this->success(
            message: 'أرقام التنبيه بالقائمة الجانبية.',
            data: $counts
        );
    }
}
