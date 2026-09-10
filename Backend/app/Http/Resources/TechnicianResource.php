<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'is_locked' => (bool) ($this->user?->locked_until && $this->user->locked_until->isFuture()),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            // FIX (تدقيق شامل — A1): بطاقتا KPI "متوسط التقييم" و"مشغول"
            // كانتا تقرآن حقلين لم يكونا موجودين إطلاقًا بهذا الـResource.
            'rating' => $this->when(
                $this->rating_avg !== null,
                fn () => round((float) $this->rating_avg, 1)
            ),
            'has_active_task' => $this->when(
                $this->active_tasks_count !== null,
                fn () => (bool) $this->active_tasks_count
            ),
            'notes' => $this->notes,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'owner_email' => $this->whenLoaded('owner', fn () => $this->owner?->email),
            // بيانات الوضع الأسري حساسة (صحية) — تظهر للإدارة فقط، حتى لو كان
            // صاحب المولد نفسه مسموح له يشوف هاد الـ resource لفنييه.
            'family_members_count' => $this->when(
                $request->user()?->isAdmin(),
                fn () => $this->user?->family_members_count
            ),
            'has_sick_family_member' => $this->when(
                $request->user()?->isAdmin(),
                fn () => (bool) $this->user?->has_sick_family_member
            ),
            'sick_family_member_illness' => $this->when(
                $request->user()?->isAdmin(),
                fn () => $this->user?->sick_family_member_illness
            ),
        ];
    }
}
