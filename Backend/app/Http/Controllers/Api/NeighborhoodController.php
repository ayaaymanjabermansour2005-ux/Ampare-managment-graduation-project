<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Neighborhood\StoreNeighborhoodRequest;
use App\Models\Neighborhood;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NeighborhoodController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            message: 'قائمة الأحياء.',
            data: Neighborhood::orderBy('name')->get(['id', 'name', 'name_en'])
        );
    }

    public function store(StoreNeighborhoodRequest $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $neighborhood = Neighborhood::create($request->validated());

        return $this->success(
            message: 'تم إضافة الحي بنجاح.',
            data: $neighborhood,
            code: 201
        );
    }

    public function update(StoreNeighborhoodRequest $request, Neighborhood $neighborhood): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $neighborhood->update($request->validated());

        return $this->success(message: 'تم تحديث الحي بنجاح.', data: $neighborhood->fresh());
    }

    public function destroy(Neighborhood $neighborhood): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $subscribersCount = $neighborhood->subscribers()->count();
        $locationsCount = $neighborhood->locations()->count();

        if ($subscribersCount > 0 || $locationsCount > 0) {
            throw ValidationException::withMessages([
                'neighborhood' => ["لا يمكن حذف هذا الحي لأنه مرتبط بـ {$subscribersCount} مشترك و{$locationsCount} موقع. يجب نقلهم لحي آخر أولاً."],
            ]);
        }

        $neighborhood->delete();

        return $this->success(message: 'تم حذف الحي بنجاح.');
    }
}
