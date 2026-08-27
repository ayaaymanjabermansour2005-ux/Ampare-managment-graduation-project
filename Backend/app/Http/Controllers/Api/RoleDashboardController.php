<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OwnerDashboardService;
use App\Services\SubscriberDashboardService;
use App\Services\TechnicianDashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group لوحات التحكم الإحصائية
 */
class RoleDashboardController extends Controller
{
    use ApiResponse;

    public function ownerStats(Request $request, OwnerDashboardService $service): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'هذه اللوحة مخصَّصة لمالكي المولّدات فقط.');
        }

        abort_unless($user->isOwner(), 403, 'هذه اللوحة مخصَّصة لمالكي المولّدات فقط.');

        return $this->success(message: 'إحصائياتك.', data: $service->stats($user));
    }

    public function subscriberStats(Request $request, SubscriberDashboardService $service): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'هذه اللوحة مخصَّصة للمشتركين فقط.');
        }

        abort_unless($user->isSubscriber(), 403, 'هذه اللوحة مخصَّصة للمشتركين فقط.');

        return $this->success(message: 'إحصائياتك.', data: $service->stats($user));
    }

    public function technicianStats(Request $request, TechnicianDashboardService $service): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'هذه اللوحة مخصَّصة للفنيين فقط.');
        }

        abort_unless($user->isTechnician(), 403, 'هذه اللوحة مخصَّصة للفنيين فقط.');

        return $this->success(message: 'إحصائياتك.', data: $service->stats($user));
    }
}
