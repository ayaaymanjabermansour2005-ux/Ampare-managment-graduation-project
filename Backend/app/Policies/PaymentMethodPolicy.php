<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment-methods.view') && ($user->isAdmin() || $user->isOwner() || $user->isTechnician());
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        if (! $user->can('payment-methods.view')) {
            return false;
        }

        if ($user->isAdmin() || $user->id === $paymentMethod->user_id) {
            return true;
        }

        // مالك المولد يمكنه الاطلاع (فقط) على وسيلة دفع فني تابع له.
        return $user->isOwner() && $paymentMethod->owner?->technician?->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('payment-methods.create') && ($user->isOwner() || $user->isTechnician());
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        if (! $user->can('payment-methods.update')) {
            return false;
        }

        return $user->isAdmin() || $user->id === $paymentMethod->user_id;
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        if (! $user->can('payment-methods.delete')) {
            return false;
        }

        return $user->isAdmin() || $user->id === $paymentMethod->user_id;
    }
}
