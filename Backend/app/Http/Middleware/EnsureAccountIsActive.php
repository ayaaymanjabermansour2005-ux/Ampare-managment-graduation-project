<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'حسابك غير مفعّل حاليًا، يرجى التواصل مع الإدارة.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        if ($user->isLocked()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($user->currentAccessToken() instanceof PersonalAccessToken) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'حسابك مقفول مؤقتًا بسبب محاولات دخول فاشلة متكررة، حاول لاحقًا.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
