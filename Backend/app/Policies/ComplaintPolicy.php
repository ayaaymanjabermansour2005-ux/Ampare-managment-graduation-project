<?php

namespace App\Policies;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('complaints.view');
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if (! $user->can('complaints.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->id === $complaint->submitted_by) {
            return true;
        }

        if ($user->isOwner()) {
            return $complaint->relatedOwnerId() === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('complaints.create')
            && ($user->isSubscriber() || $user->isOwner() || $user->isTechnician());
    }

    public function resolve(User $user, Complaint $complaint): bool
    {
        if (! $user->can('complaints.resolve')) {
            return false;
        }

        if ($complaint->status === ComplaintStatus::Resolved) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $complaint->relatedOwnerId() === $user->id;
    }

    /**
     * تعيين/إلغاء تعيين "المسؤول" عن الشكوى — إداري بحت (تصنيف داخلي لتوزيع
     * العمل بين الأدمن)، بعكس resolve() فما بتخضع لقيد "مش محلولة أصلًا"،
     * ومقتصرة على الأدمن (نفس صلاحية complaints.resolve، بلا حاجة لصلاحية
     * جديدة منفصلة).
     */
    public function assign(User $user): bool
    {
        return $user->can('complaints.resolve') && $user->isAdmin();
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.delete') && $user->isAdmin();
    }

    public function manageAttachments(User $user, Complaint $complaint): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $complaint->submitted_by === (int) $user->id;
    }
}
