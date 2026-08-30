<?php

namespace App\Http\Controllers\Api;

use App\Enums\GeneratorStatus;
use App\Http\Controllers\Controller;
use App\Models\Generator;
use App\Models\MeterReading;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\PlatformUptimeCalculator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class PublicPlatformStatsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PlatformUptimeCalculator $uptimeCalculator
    ) {}

    public function index(): JsonResponse
    {
        $stats = Cache::remember('public_platform_stats_v2', now()->addMinutes(30), function () {
            return [
                'subscribers_count' => Subscriber::count(),
                'active_generators_count' => Generator::where('status', GeneratorStatus::Active->value)->count(),
                'owners_count' => User::whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))->count(),
                'meter_readings_count' => MeterReading::count(),
                'uptime_percentage' => $this->uptimeCalculator->calculate(periodDays: 30),
            ];
        });

        return $this->success(
            message: 'إحصائيات المنصة العامة.',
            data: $stats
        );
    }
}
