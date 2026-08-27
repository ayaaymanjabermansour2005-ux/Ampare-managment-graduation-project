<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(message: 'خطط الاشتراك.', data: PlanResource::collection(Plan::where('is_active', true)->get()));
    }

    public function assign(Request $request, User $user): JsonResponse
    {
        $this->authorize('assignPlan', $user);

        $validated = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);

        $user->forceFill(['plan_id' => $validated['plan_id']])->save();

        activity()->causedBy($request->user())->performedOn($user)->log('admin_assigned_plan');

        return $this->success(message: 'تم تعيين الخطة بنجاح.', data: $user->fresh('plan'));
    }
}
