<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\TechnicianPaymentStatus;
use App\Enums\TechnicianTaskStatus;
use App\Models\TechnicianPayment;
use App\Models\TechnicianRating;
use App\Models\TechnicianTask;
use App\Models\User;

class TechnicianDashboardService
{
    public function stats(User $user): array
    {
        $technician = $user->technician;

        if (! $technician) {
            return [
                'active_tasks_count' => 0,
                'pending_review_tasks_count' => 0,
                'completed_tasks_count' => 0,
                'average_rating' => null,
                'ratings_count' => 0,
                'pending_payments_count' => 0,
                'total_paid_ils' => 0.0,
                'unread_notifications_count' => $user->unreadNotifications()->count(),
            ];
        }

        $ratingsCount = TechnicianRating::where('technician_id', $technician->id)->count();

        return [
            'active_tasks_count' => TechnicianTask::where('technician_id', $technician->id)
                ->whereIn('status', [
                    TechnicianTaskStatus::Assigned->value,
                    TechnicianTaskStatus::OnTheWay->value,
                    TechnicianTaskStatus::InProgress->value,
                    TechnicianTaskStatus::WaitingParts->value,
                ])->count(),

            'pending_review_tasks_count' => TechnicianTask::where('technician_id', $technician->id)
                ->where('status', TechnicianTaskStatus::Submitted->value)->count(),

            'completed_tasks_count' => TechnicianTask::where('technician_id', $technician->id)
                ->where('status', TechnicianTaskStatus::Approved->value)->count(),

            'average_rating' => $ratingsCount > 0
                ? round((float) TechnicianRating::where('technician_id', $technician->id)->avg('rating'), 2)
                : null,

            'ratings_count' => $ratingsCount,

            'pending_payments_count' => TechnicianPayment::where('technician_id', $technician->id)
                ->where('status', TechnicianPaymentStatus::Pending->value)->count(),

            'total_paid_ils' => (float) TechnicianPayment::where('technician_id', $technician->id)
                ->where('status', TechnicianPaymentStatus::Approved->value)
                ->where('currency', Currency::ILS->value)
                ->sum('amount'),

            'unread_notifications_count' => $user->unreadNotifications()->count(),
        ];
    }
}
