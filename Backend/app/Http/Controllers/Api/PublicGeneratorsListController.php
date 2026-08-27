<?php

namespace App\Http\Controllers\Api;

use App\Enums\GeneratorStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Generator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class PublicGeneratorsListController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $data = Cache::remember('public_generators_list_v2', now()->addMinutes(30), function () {
            $generators = Generator::query()
                ->join('locations', 'generators.location_id', '=', 'locations.id')
                ->where('generators.status', GeneratorStatus::Active->value)
                ->whereNotNull('locations.latitude')
                ->whereNotNull('locations.longitude')
                ->withCount(['subscriptions as active_subscriptions_count' => fn ($q) => $q->where('status', SubscriptionStatus::Active->value)])
                ->select(['generators.id', 'generators.name', 'generators.name_en', 'generators.price_per_kw', 'generators.currency', 'locations.city'])
                ->selectRaw('AVG(locations.latitude) OVER (PARTITION BY locations.city) as city_lat')
                ->selectRaw('AVG(locations.longitude) OVER (PARTITION BY locations.city) as city_lng')
                ->get()
                ->map(function ($g) {
                    $seed = crc32((string) $g->id);
                    $offsetLat = ((($seed % 200) - 100) / 100) * 0.007;
                    $offsetLng = (((intdiv($seed, 200) % 200) - 100) / 100) * 0.007;

                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'name_en' => $g->name_en,
                        'city' => $g->city,
                        'price_per_kw' => (float) $g->price_per_kw,
                        'currency' => $g->currency,
                        'subscribers_count' => $g->active_subscriptions_count,
                        'latitude' => round((float) $g->city_lat + $offsetLat, 6),
                        'longitude' => round((float) $g->city_lng + $offsetLng, 6),
                    ];
                });

            return [
                'generators' => $generators,
                'total_generators' => $generators->count(),
                'total_cities' => $generators->pluck('city')->unique()->count(),
            ];
        });

        return $this->success(
            message: 'قائمة المولدات النشطة العامة.',
            data: $data
        );
    }
}
