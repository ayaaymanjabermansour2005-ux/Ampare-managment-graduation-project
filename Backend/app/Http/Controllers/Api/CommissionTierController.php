<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionTier\StoreCommissionTierRequest;
use App\Http\Requests\CommissionTier\UpdateCommissionTierRequest;
use App\Http\Resources\CommissionTierResource;
use App\Models\CommissionTier;
use App\Services\CommissionTierService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group 	  				شرائح العمولة التلقائية
 */
class CommissionTierController extends Controller
{
    use ApiResponse;

    public function __construct(protected CommissionTierService $service) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CommissionTier::class);

        return $this->success(
            message: 'قائمة شرائح العمولة.',
            data: CommissionTierResource::collection($this->service->list())
        );
    }

    public function store(StoreCommissionTierRequest $request): JsonResponse
    {
        $this->authorize('create', CommissionTier::class);

        $tier = $this->service->create($request->validated());

        return $this->success(
            message: 'تم إنشاء شريحة العمولة بنجاح.',
            data: new CommissionTierResource($tier),
            code: 201
        );
    }

    public function update(UpdateCommissionTierRequest $request, CommissionTier $commissionTier): JsonResponse
    {
        $this->authorize('update', $commissionTier);

        $tier = $this->service->update($commissionTier, $request->validated());

        return $this->success(
            message: 'تم تحديث شريحة العمولة بنجاح.',
            data: new CommissionTierResource($tier)
        );
    }

    public function destroy(CommissionTier $commissionTier): JsonResponse
    {
        $this->authorize('delete', $commissionTier);

        $this->service->delete($commissionTier);

        return $this->success(message: 'تم حذف شريحة العمولة بنجاح.');
    }
}
