<?php

namespace App\Http\Controllers\Api;

use App\Enums\GeneratorStatus;
use App\Http\Controllers\Controller;
use App\Models\Generator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class PublicGeneratorsMapController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $cities = Cache::remember('public_generators_map_by_city', now()->addMinutes(30), function () {
            return Generator::query()
                ->join('locations', 'generators.location_id', '=', 'locations.id')
                ->where('generators.status', GeneratorStatus::Active->value)
                ->whereNotNull('locations.latitude')
                ->whereNotNull('locations.longitude')
                ->selectRaw('locations.city as city')
                ->selectRaw('count(*) as active_generators_count')
                ->selectRaw('avg(locations.latitude) as latitude')
                ->selectRaw('avg(locations.longitude) as longitude')
                ->groupBy('locations.city')
                ->orderByDesc('active_generators_count')
                ->get();
        });

        return $this->success(
            message: 'خريطة تغطية المولدات النشطة حسب المدينة.',
            data: [
                'cities' => $cities,
                'total_active_generators' => $cities->sum('active_generators_count'),
                'total_cities' => $cities->count(),
            ]
        );
    }
}
