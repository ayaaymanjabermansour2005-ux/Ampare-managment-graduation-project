<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Policies\NeighborhoodDashboardPolicy;
use App\Services\NeighborhoodDashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class NeighborhoodDashboardController extends Controller
{
    use ApiResponse;

    public function index(NeighborhoodDashboardService $service, NeighborhoodDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        return $this->success(
            message: 'ملخص الأحياء.',
            data: $service->summary()
        );
    }
}
