<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $user->can('invoices.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $subscription = $invoice->subscription;

        if ($user->isOwner()) {
            return $user->id === $subscription?->generator?->owner_id;
        }

        if ($user->isSubscriber()) {
            return $user->id === $subscription?->subscriberMeter?->subscriber?->user_id;
        }

        return false;
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.cancel') && $user->isAdmin();
    }

    public function correct(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.correct') && $user->isAdmin();
    }

    public function reissue(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.reissue') && $user->isAdmin();
    }
}
