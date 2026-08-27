<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\CommissionTier;
use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Fault;
use App\Models\FaultPrediction;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Offer;
use App\Models\OwnerApplication;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PlatformCommission;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\TechnicianTask;
use App\Models\User;
use App\Policies\AttachmentPolicy;
use App\Policies\CommissionTierPolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\FaultPolicy;
use App\Policies\FaultPredictionPolicy;
use App\Policies\GeneratorPolicy;
use App\Policies\GeneratorSchedulePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MeterReadingPolicy;
use App\Policies\OfferPolicy;
use App\Policies\OwnerApplicationPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PlatformCommissionPolicy;
use App\Policies\SubscriberMeterPolicy;
use App\Policies\SubscriptionMeterTransferRequestPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TechnicianPaymentPolicy;
use App\Policies\TechnicianPolicy;
use App\Policies\TechnicianTaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Generator::class => GeneratorPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        SubscriptionMeterTransferRequest::class => SubscriptionMeterTransferRequestPolicy::class,
        SubscriberMeter::class => SubscriberMeterPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        PlatformCommission::class => PlatformCommissionPolicy::class,
        Fault::class => FaultPolicy::class,
        FaultPrediction::class => FaultPredictionPolicy::class,
        Complaint::class => ComplaintPolicy::class,
        Offer::class => OfferPolicy::class,
        Conversation::class => ConversationPolicy::class,
        MeterReading::class => MeterReadingPolicy::class,
        Technician::class => TechnicianPolicy::class,
        TechnicianTask::class => TechnicianTaskPolicy::class,
        TechnicianPayment::class => TechnicianPaymentPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        GeneratorSchedule::class => GeneratorSchedulePolicy::class,
        OwnerApplication::class => OwnerApplicationPolicy::class,
        // إضافة (حزمة #2): شرائح العمولة التلقائية — أدمن فقط.
        CommissionTier::class => CommissionTierPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
