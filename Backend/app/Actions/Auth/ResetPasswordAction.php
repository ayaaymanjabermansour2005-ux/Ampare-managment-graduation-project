<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\ResetPasswordData;
use App\Models\User;
use App\Support\Auth\PasswordResetStatusTranslator;
use App\Support\Auth\SessionInvalidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(
        protected SessionInvalidator $sessionInvalidator,
        protected PasswordResetStatusTranslator $statusTranslator,
    ) {}

    public function execute(ResetPasswordData $data): void
    {
        $status = Password::broker('users')->reset(
            $data->toBrokerCredentials(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $this->sessionInvalidator->invalidateAllFor($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [$this->statusTranslator->translate($status)],
            ]);
        }
    }
}
