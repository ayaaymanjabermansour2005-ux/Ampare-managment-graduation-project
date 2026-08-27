<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group الصفحة العامة (بدون تسجيل دخول)
 */
class GuestDemoLoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'in:subscriber,owner'],
        ]);

        $email = $request->input('role') === 'owner'
            ? 'demo-owner@ampare.test'
            : 'demo-subscriber@ampare.test';

        $demoUser = User::where('email', $email)
            ->where('is_guest_demo', true)
            ->first();

        if (! $demoUser) {
            throw ValidationException::withMessages([
                'role' => ['الحساب التجريبي غير متوفر حاليًا، حاولي لاحقًا.'],
            ]);
        }

        Auth::guard('web')->login($demoUser);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success(
            message: 'أهلًا فيكِ بالوضع التجريبي — استعراض بس، بدون حفظ فعلي.',
            data: new UserResource($demoUser)
        );
    }
}
