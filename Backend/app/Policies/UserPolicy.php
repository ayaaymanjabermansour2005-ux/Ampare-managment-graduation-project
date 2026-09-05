<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view') && $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return ($user->can('users.view') && $user->isAdmin()) || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('users.create') && $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return ($user->can('users.update') && $user->isAdmin()) || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.delete') && $user->isAdmin() && $user->id !== $model->id;
    }

    public function unlock(User $user, User $model): bool
    {
        return $user->can('users.unlock') && $user->isAdmin() && $user->id !== $model->id;
    }

    public function assignPlan(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * بحث محدود (اسم/بريد/هاتف فقط) عن مستخدمين بدور "مشترك" — مخصَّص لمالك
     * المولد عند تسجيل مشترك جديد على مولده (خطوة "إضافة اشتراك"). قدرة
     * على مستوى الكلاس (discovery-only) لأن العلاقة بين المالك والمشترك
     * لسه مش موجودة وقت البحث — هي بالضبط اللي هذا الإجراء بينشئها.
     * متعمَّد إنها منفصلة عن viewAny() (أدمن فقط، وترجع بيانات كاملة عبر
     * UserResource) — هنا فقط id/name/email/phone عبر Resource مخصَّص.
     */
    public function lookupForOwner(User $user): bool
    {
        // الأدمن كمان بيستخدم نفس مسار البحث/العدادات هذا عند إنشاء اشتراك
        // يدويًا من لوحة التحكم (SubscribersView.vue) — نفس نمط
        // owner-monthly-report/download (role:admin|generator_owner).
        return $user->isOwner() || $user->isAdmin();
    }

    /**
     * تذكير دفع جماعي من قِبَل مالك المولد — قدرة على مستوى الكلاس لأن
     * الإجراء يعمل على مجموعة (array) من subscriber_ids وليس نموذج واحد؛
     * النطاق الفعلي (كل subscriber_id يجب أن يكون له اشتراك على مولد يملكه
     * هذا المالك تحديدًا) يتحقق منه SendOwnerBulkPaymentReminderRequest.
     */
    public function sendBulkPaymentReminderAsOwner(User $user): bool
    {
        return $user->isOwner();
    }

    public function manageCommissionSettings(User $user, User $model): bool
    {
        return $user->can('platform-commissions.manage-settings') && $user->isAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
