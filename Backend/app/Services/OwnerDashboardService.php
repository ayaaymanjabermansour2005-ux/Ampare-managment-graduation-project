<?php

namespace App\Services;

use App\Enums\FaultStatus;
use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianTaskStatus;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TechnicianTask;
use App\Models\User;

class OwnerDashboardService
{
    public function stats(User $owner): array
    {
        $generatorIds = Generator::where('owner_id', $owner->id)->pluck('id');

        return [
            'generators_count' => $generatorIds->count(),
            'active_generators_count' => Generator::where('owner_id', $owner->id)
                ->where('status', GeneratorStatus::Active->value)->count(),
            'pending_verification_generators_count' => Generator::where('owner_id', $owner->id)
                ->where('status', GeneratorStatus::PendingVerification->value)->count(),

            'active_subscriptions_count' => Subscription::whereIn('generator_id', $generatorIds)
                ->where('status', SubscriptionStatus::Active)->count(),

            'pending_invoices_count' => Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('generator_id', $generatorIds)
            )->where('status', InvoiceStatus::Pending)->count(),

            'overdue_invoices_count' => Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('generator_id', $generatorIds)
            )->where('status', InvoiceStatus::Overdue)->count(),

            'total_revenue_ils' => (float) Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('generator_id', $generatorIds)
            )->where('status', InvoiceStatus::Paid)->sum('final_amount_ils'),

            'outstanding_invoices_total_ils' => (float) Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('generator_id', $generatorIds)
            )->whereIn('status', [
                InvoiceStatus::Pending->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])->sum('final_amount_ils'),

            'pending_payments_count' => Payment::whereHas(
                'invoice.subscription',
                fn ($q) => $q->whereIn('generator_id', $generatorIds)
            )->where('status', PaymentStatus::Pending)->count(),

            'open_faults_count' => Fault::whereIn('generator_id', $generatorIds)
                ->whereIn('status', [
                    FaultStatus::PendingVerification->value,
                    FaultStatus::Verified->value,
                    FaultStatus::InRepair->value,
                ])->count(),

            'pending_technician_tasks_count' => TechnicianTask::whereIn('generator_id', $generatorIds)
                ->whereIn('status', [
                    TechnicianTaskStatus::Pending->value,
                    TechnicianTaskStatus::Assigned->value,
                    TechnicianTaskStatus::Submitted->value,
                ])->count(),

            'unread_notifications_count' => $owner->unreadNotifications()->count(),
        ];
    }
}
