<?php

namespace App\Providers;

use App\Events\ComplaintResolved;
use App\Events\ComplaintSubmitted;
use App\Events\ContactMessageReceived;
use App\Events\FaultReported;
use App\Events\FuelStockLow;
use App\Events\GeneratorHealthReportGenerated;
use App\Events\GeneratorScheduleAnnounced;
use App\Events\InvoiceDueSoon;
use App\Events\InvoiceOverdue;
use App\Events\InvoicePaid;
use App\Events\MessageSent;
use App\Events\PaymentApproved;
use App\Events\PaymentRejected;
use App\Events\PaymentSubmitted;
use App\Events\SubscriptionApproved;
use App\Events\TechnicianTaskApproved;
use App\Events\TechnicianTaskAssigned;
use App\Events\TechnicianTaskRejected;
use App\Events\TechnicianTaskSubmitted;
use App\Listeners\CheckApplicationDependenciesHealth;
use App\Listeners\ClearAdminDashboardCache;
use App\Listeners\SendComplaintResolvedNotification;
use App\Listeners\SendFaultReportedAdminNotification;
use App\Listeners\SendFaultReportedNotification;
use App\Listeners\SendFuelStockLowNotification;
use App\Listeners\SendGeneratorHealthReportNotification;
use App\Listeners\SendGeneratorScheduleAnnouncedNotification;
use App\Listeners\SendInvoiceDueSoonNotification;
use App\Listeners\SendInvoiceOverdueNotification;
use App\Listeners\SendInvoicePaidNotification;
use App\Listeners\SendMessageNotification;
use App\Listeners\SendNewComplaintNotification;
use App\Listeners\SendNewContactMessageNotification;
use App\Listeners\SendPaymentSubmittedNotification;
use App\Listeners\SendSubscriptionApprovedNotification;
use App\Listeners\SendTechnicianTaskAssignedNotification;
use App\Listeners\SendTechnicianTaskReviewedNotification;
use App\Listeners\SendTechnicianTaskSubmittedNotification;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DiagnosingHealth::class => [
            CheckApplicationDependenciesHealth::class,
        ],

        InvoicePaid::class => [
            SendInvoicePaidNotification::class,
            ClearAdminDashboardCache::class,
        ],

        SubscriptionApproved::class => [
            SendSubscriptionApprovedNotification::class,
            ClearAdminDashboardCache::class,
        ],

        FaultReported::class => [
            SendFaultReportedNotification::class,
            SendFaultReportedAdminNotification::class,
            ClearAdminDashboardCache::class,
        ],

        ComplaintResolved::class => [
            SendComplaintResolvedNotification::class,
            ClearAdminDashboardCache::class,
        ],

        ComplaintSubmitted::class => [
            SendNewComplaintNotification::class,
            ClearAdminDashboardCache::class,
        ],

        PaymentApproved::class => [
            ClearAdminDashboardCache::class,
        ],

        PaymentRejected::class => [
            ClearAdminDashboardCache::class,
        ],

        InvoiceOverdue::class => [
            SendInvoiceOverdueNotification::class,
        ],

        MessageSent::class => [
            SendMessageNotification::class,
        ],

        PaymentSubmitted::class => [
            SendPaymentSubmittedNotification::class,
            ClearAdminDashboardCache::class,
        ],

        ContactMessageReceived::class => [
            SendNewContactMessageNotification::class,
        ],

        TechnicianTaskAssigned::class => [
            SendTechnicianTaskAssignedNotification::class,
        ],

        TechnicianTaskSubmitted::class => [
            SendTechnicianTaskSubmittedNotification::class,
        ],

        TechnicianTaskApproved::class => [
            [SendTechnicianTaskReviewedNotification::class, 'handleApproved'],
        ],

        TechnicianTaskRejected::class => [
            [SendTechnicianTaskReviewedNotification::class, 'handleRejected'],
        ],

        GeneratorScheduleAnnounced::class => [
            SendGeneratorScheduleAnnouncedNotification::class,
        ],

        FuelStockLow::class => [
            SendFuelStockLowNotification::class,
        ],

        GeneratorHealthReportGenerated::class => [
            SendGeneratorHealthReportNotification::class,
        ],

        InvoiceDueSoon::class => [
            SendInvoiceDueSoonNotification::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
