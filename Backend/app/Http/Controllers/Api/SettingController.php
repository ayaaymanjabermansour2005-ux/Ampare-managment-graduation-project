<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(protected SettingService $settingService) {}

    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        return $this->success(message: 'إعدادات النظام.', data: $this->settingService->all());
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $this->settingService->update($validated['settings']);

        return $this->success(message: 'تم حفظ الإعدادات بنجاح.');
    }

    public function publicIndex(): JsonResponse
    {
        return $this->success(message: 'هوية المنصة.', data: $this->settingService->public());
    }
}
