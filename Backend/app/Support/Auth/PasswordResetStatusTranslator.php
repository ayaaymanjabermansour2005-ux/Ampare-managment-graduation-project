<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\Password;

class PasswordResetStatusTranslator
{
    public function translate(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER, Password::INVALID_TOKEN => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.',
            Password::RESET_THROTTLED => 'يرجى الانتظار قبل المحاولة مرة أخرى.',
            default => 'تعذّر إعادة تعيين كلمة المرور، حاول مرة أخرى.',
        };
    }
}
