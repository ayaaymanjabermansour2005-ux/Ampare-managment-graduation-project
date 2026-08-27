<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SendPasswordResetLinkAction;
use App\DTOs\Auth\ResetPasswordData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group  المصادقة وإدارة الحساب
 */
class PasswordResetController extends Controller
{
    use ApiResponse;

    public function sendResetLink(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->execute($request->validated('email'));

        return $this->success(
            message: 'إذا كان البريد الإلكتروني مسجّلًا لدينا، فسيصلك رابط إعادة تعيين كلمة المرور خلال دقائق.'
        );
    }

    public function reset(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->execute(
            ResetPasswordData::fromArray($request->validated())
        );

        return $this->success(
            message: 'تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة.'
        );
    }
}
