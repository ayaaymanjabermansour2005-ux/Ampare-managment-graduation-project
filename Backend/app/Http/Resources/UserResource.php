<?php

namespace App\Http\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'location' => $this->latitude && $this->longitude ? [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ] : null,
            'bio' => $this->bio,
            'whatsapp' => $this->whatsapp,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'email_verified' => $this->hasVerifiedEmail(),
            'roles' => $this->roles->map(fn ($role) => ['name' => $role->name]),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
            ] : null),
            'generators_count' => $this->resolveGeneratorsCount(),
            'active_subscriptions_count' => $this->resolveActiveSubscriptionsCount(),
            'monthly_revenue_ils' => $this->when(
                $this->relationLoaded('generators'),
                fn () => (float) $this->generators->sum('monthly_revenue_ils')
            ),
            'commission_rate' => $this->whenLoaded(
                'latestCommissionRate',
                fn () => $this->latestCommissionRate ? (float) $this->latestCommissionRate->commission_rate : null
            ),
            'commission_settings' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                fn () => [
                    'mode' => $this->commission_mode?->value,
                    'mode_label' => $this->commission_mode?->label(),
                    'rate' => $this->commission_rate !== null ? (float) $this->commission_rate : null,
                ]
            ),
            // بيانات الوضع الأسري: حساسة (صحية)، فمكشوفة فقط لصاحب الحساب نفسه أو للإدارة —
            // نفس نمط تقييد commission_settings أعلاه.
            'family_members_count' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                fn () => $this->family_members_count
            ),
            'has_sick_family_member' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                fn () => (bool) $this->has_sick_family_member
            ),
            'sick_family_member_illness' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                fn () => $this->sick_family_member_illness
            ),
            'beneficiary_type' => $this->whenLoaded('subscriber', fn () => $this->subscriber?->beneficiary_type?->value),
            'beneficiary_type_label' => $this->whenLoaded('subscriber', fn () => $this->subscriber?->beneficiary_type?->label()),
            'region' => $this->whenLoaded('subscriber', fn () => $this->subscriber?->neighborhood?->name),
            'active_subscription' => $this->when(
                $this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions'),
                function () {
                    $active = $this->subscriber->subscriptions
                        ->firstWhere('status', SubscriptionStatus::Active);
                    if (! $active) {
                        return null;
                    }

                    return [
                        'id' => $active->id,
                        'generator_name' => $active->generator?->name,
                        'ends_at' => $active->end_date?->toDateString(),
                    ];
                }
            ),
            'subscription_history' => $this->when(
                $this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions'),
                fn () => $this->subscriber->subscriptions
                    ->sortByDesc('created_at')
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'generator_name' => $s->generator?->name,
                        'status' => $s->status?->value,
                        'status_label' => $s->status?->label(),
                        'start_date' => $s->start_date?->toDateString(),
                        'end_date' => $s->end_date?->toDateString(),
                    ])
                    ->values()
                    ->all()
            ),
            'subscriptions_history' => $this->when(
                $this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions'),
                fn () => $this->subscriber->subscriptions
                    ->sortByDesc('start_date')
                    ->map(fn ($sub) => [
                        'id' => $sub->id,
                        'generator_name' => $sub->generator?->name,
                        'status' => $sub->status?->value,
                        'status_label' => $sub->status?->label(),
                        'start_date' => $sub->start_date?->toDateString(),
                        'end_date' => $sub->end_date?->toDateString(),
                    ])
                    ->values()
            ),
            'outstanding_balance_ils' => $this->when(
                $this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions'),
                fn () => (float) $this->subscriber->subscriptions->flatMap->invoices->sum('final_amount_ils')
            ),
            'payment_status' => $this->when(
                $this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions'),
                function () {
                    if ($this->status?->value === UserStatus::Suspended->value) {
                        return 'suspended';
                    }
                    $hasOverdue = $this->subscriber->subscriptions
                        ->flatMap->invoices
                        ->contains(fn ($invoice) => $invoice->status?->value === InvoiceStatus::Overdue->value);

                    return $hasOverdue ? 'overdue' : 'active';
                }
            ),
            'is_locked' => $this->when(
                $request->user()?->isAdmin(),
                fn () => $this->isLocked()
            ),
            'locked_until' => $this->when(
                $request->user()?->isAdmin(),
                fn () => $this->locked_until?->toDateTimeString()
            ),
            'lockout_count' => $this->when(
                $request->user()?->isAdmin(),
                fn () => $this->lockout_count
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    private function resolveGeneratorsCount()
    {
        if ($this->relationLoaded('generators')) {
            return $this->whenCounted('generators');
        }

        if ($this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions')) {
            return $this->subscriber->subscriptions->pluck('generator_id')->unique()->count();
        }

        return $this->when(false, fn () => null);
    }

    private function resolveActiveSubscriptionsCount()
    {
        if ($this->relationLoaded('generators')) {
            return $this->generators->sum('active_subscriptions_count');
        }

        if ($this->relationLoaded('subscriber') && $this->subscriber?->relationLoaded('subscriptions')) {
            return $this->subscriber->subscriptions->where('status', SubscriptionStatus::Active)->count();
        }

        return $this->when(false, fn () => null);
    }
}
