<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserPreferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group تفضيلات المستخدم
 */
class UserPreferenceController extends Controller
{
    use ApiResponse;

    public function index(Request $request, UserPreferenceService $service): JsonResponse
    {
        return $this->success(
            message: 'تفضيلاتك.',
            data: $service->list($request->user())
        );
    }

    public function update(Request $request, UserPreferenceService $service): JsonResponse
    {
        $validated = $request->validate(['preferences' => ['required', 'array']]);

        return $this->success(
            message: 'تم حفظ تفضيلاتك بنجاح.',
            data: $service->update($request->user(), $validated['preferences'])
        );
    }
}
