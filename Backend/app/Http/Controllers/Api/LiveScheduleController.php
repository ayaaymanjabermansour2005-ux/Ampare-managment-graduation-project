<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveScheduleController extends Controller
{
    use ApiResponse;

    public function index(Request $request, LiveScheduleService $service): JsonResponse
    {
        $neighborhoodId = $request->integer('neighborhood_id') ?: null;

        return $this->success(
            message: 'الجدول الحي للمولدات.',
            data: [
                'active_now' => $service->activeNowByNeighborhood($neighborhoodId),
                'upcoming' => $service->upcomingByNeighborhood($neighborhoodId),
            ]
        );
    }
}
