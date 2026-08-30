<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginCredentials;
use App\Enums\UserStatus;
use App\Exceptions\EmailNotVerifiedException;
use App\Models\Setting;
use App\Models\User;
use App\Services\AccountLockoutService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(
        protected AccountLockoutService $lockoutService
    ) {}

    public function execute(LoginCredentials $credentials): User
    {
        $identifierField = $credentials->identifierField();
        $user = User::where($identifierField, $credentials->login)->first();

        $genericError = fn () => throw ValidationException::withMessages([
            'login' => ['بيانات الدخول غير صحيحة.'],
        ]);

        if ($user && $this->lockoutService->isLocked($user)) {
            $genericError();
        }

        if ($user && $user->status === UserStatus::PendingReview) {
            throw ValidationException::withMessages([
                'login' => ['حسابك بانتظار مراجعة الإدارة قبل التفعيل. سيتم إعلامك فور الاعتماد.'],
            ]);
        }

        if ($user && $user->status !== UserStatus::Active) {
            $genericError();
        }

        if (! Auth::guard('web')->attempt($credentials->toArray(), $credentials->remember)) {
            if ($user) {
                $this->lockoutService->registerFailedAttempt($user);

                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'failed_attempts' => $user->failed_login_attempts,
                    ])
                    ->log('login_failed');
            }

            $genericError();
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        $this->lockoutService->resetOnSuccessfulLogin($user);

        $requireEmailVerification = in_array(
            Setting::get('require_email_verification', '1'),
            ['1', 1, true, 'true'],
            true
        );

        if ($requireEmailVerification && ! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();

            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            throw new EmailNotVerifiedException;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        activity()
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('login_succeeded');

        return $user;
    }
}
