<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\ResendVerificationEmailAction;
use App\Actions\Auth\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @group  المصادقة وإدارة الحساب
 */
class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function verify(Request $request, int $id, string $hash, VerifyEmailAction $action): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

        if (! $request->hasValidSignature()) {
            return redirect()->away("{$frontendUrl}/login?verified=expired");
        }

        $verified = $action->execute($id, $hash);

        return redirect()->away(
            $verified
                ? "{$frontendUrl}/login?verified=1"
                : "{$frontendUrl}/login?verified=0"
        );
    }

    public function resend(ResendVerificationRequest $request, ResendVerificationEmailAction $action): JsonResponse
    {
        $action->execute($request->validated('email'));

        return $this->success(
            message: 'إذا كان البريد الإلكتروني مسجلاً وغير مفعّل، تم إرسال رابط تفعيل جديد إليه.'
        );
    }
}
