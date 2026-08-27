<?php

namespace App\Http\Controllers\Api;

use App\Actions\Subscriber\UpdateBeneficiaryTypeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriber\UpdateBeneficiaryTypeRequest;
use App\Http\Resources\SubscriberResource;
use App\Models\Subscriber;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group 	المشتركين والعدادات
 */
class SubscriberController extends Controller
{
    use ApiResponse;

    public function updateBeneficiaryType(
        UpdateBeneficiaryTypeRequest $request,
        Subscriber $subscriber,
        UpdateBeneficiaryTypeAction $action
    ): JsonResponse {
        $subscriber = $action->execute(
            $subscriber,
            $request->validated('beneficiary_type'),
            $request->user()
        );

        return $this->success(
            message: 'تم تحديث تصنيف المشترك بنجاح.',
            data: new SubscriberResource($subscriber)
        );
    }
}
