<?php

namespace App\Policies;

use App\Models\TechnicianPayment;
use App\Models\User;

class TechnicianPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('technician-payments.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isTechnician());
    }

    public function view(User $user, TechnicianPayment $payment): bool
    {
        if (! $user->can('technician-payments.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $payment->owner_id === $user->id;
        }

        return $payment->technician?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('technician-payments.create') && $user->isOwner();
    }

    /**
     * Admin is deliberately excluded here even though Admin holds every
     * permission string — Technician Payments are an internal Owner<->Technician
     * transaction and must stay strictly read/audit-only for Admin.
     */
    public function approve(User $user, TechnicianPayment $payment): bool
    {
        if (! $user->can('technician-payments.approve') || ! $user->isTechnician()) {
            return false;
        }

        return $payment->technician?->user_id === $user->id
            && $payment->status->isReviewable();
    }

    public function reject(User $user, TechnicianPayment $payment): bool
    {
        if (! $user->can('technician-payments.reject') || ! $user->isTechnician()) {
            return false;
        }

        return $payment->technician?->user_id === $user->id
            && $payment->status->isReviewable();
    }

    public function manageAttachments(User $user, TechnicianPayment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $payment->owner_id === $user->id;
        }

        return $payment->technician?->user_id === $user->id;
    }
}
