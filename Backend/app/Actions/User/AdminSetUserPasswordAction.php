<?php

namespace App\Actions\User;

use App\Models\User;
use App\Support\Auth\SessionInvalidator;
use Illuminate\Support\Facades\Hash;

class AdminSetUserPasswordAction
{
    public function __construct(
        protected SessionInvalidator $sessionInvalidator
    ) {}

    public function execute(User $user, string $newPassword, User $admin): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        // إبطال كل جلسات المستخدم الحالية فور تغيير كلمة السر — إجراء أمني
        // قياسي، يمنع أي جلسة قديمة مسروقة من الاستمرار بعد إعادة التعيين.
        $this->sessionInvalidator->invalidateAllFor($user);

        activity()
            ->causedBy($admin)
            ->performedOn($user)
            ->log('password_set_by_admin');
    }
}
