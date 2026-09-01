<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Enums\PlatformCommissionStatus;
use App\Events\InvoiceOverdue;
use App\Events\InvoicePaid;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\PaymentMethod;
use App\Models\PlatformCommission;
use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService,
        protected OfferService $offerService,
        protected CommissionRateResolver $commissionRateResolver,
    ) {}

    public function list(
        User $user,
        int $perPage = 15,
        ?string $search = null,
        ?int $subscriberUserId = null,
        ?array $statuses = null
    ): LengthAwarePaginator {
        $query = Invoice::query()->with([
            'subscription.generator',
            'subscription.subscriberMeter.subscriber.user',
            'payments.paymentMethod',
            'commission',
        ]);
        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isSubscriber()) {
            $query->whereHas('subscription.subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas(
                        'subscription.subscriberMeter.subscriber.user',
                        fn ($userQ) => $userQ->where('name', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'subscription.generator',
                        fn ($genQ) => $genQ->where('name', 'like', "%{$search}%")
                    );
            });
        }

        // فلترة إضافية لاستخدامات محددة كـ "تسجيل دفعة يدوية" بلوحة الأدمن —
        // إيجاد فواتير مشترك معيّن بدقة (بمعرّفه، لا بالاسم الملتبس عبر search).
        if ($subscriberUserId) {
            $query->whereHas(
                'subscription.subscriberMeter.subscriber.user',
                fn ($userQ) => $userQ->where('id', $subscriberUserId)
            );
        }

        if ($statuses) {
            $query->whereIn('status', $statuses);
        }

        return $query->latest()->paginate($perPage);
    }

    public function createFromMeterReading(MeterReading $meterReading, Subscription $subscription): Invoice
    {
        $existingInvoice = $meterReading->invoice()->first();
        if ($existingInvoice) {
            return $existingInvoice;
        }

        return DB::transaction(function () use ($meterReading, $subscription) {
            $amount = Money::mul(
                (float) $meterReading->consumed_kw,
                (float) $subscription->agreed_price_per_kw,
                2
            );
            $dueDays = config('billing.invoice_due_days', 7);
            $bestOffer = $this->offerService->resolveBestOffer($subscription, $amount);
            $discountAmount = $bestOffer['discount_amount'] ?? 0.0;
            $discountId = $bestOffer['offer']?->id ?? null;
            $finalAmount = Money::sub($amount, $discountAmount, 2);
            $currency = $subscription->currency;
            [$exchangeRate, $amountIls] = $this->exchangeRateService->toIls($finalAmount, $currency);

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'meter_reading_id' => $meterReading->id,
                'amount' => $amount,
                'discount_amount' => $discountAmount,
                'discount_id' => $discountId,
                'final_amount' => $finalAmount,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'final_amount_ils' => $amountIls,
                'due_date' => Carbon::parse($meterReading->reading_date)->addDays($dueDays),
            ]);

            $this->createCommission($invoice, $subscription->generator->owner, $amountIls);

            SendInvoiceEmail::dispatch($invoice->id)->afterCommit();

            return $invoice->fresh(['commission', 'payments', 'appliedOffer']);
        });
    }

    public function createFromServiceRequest(SubscriptionServiceRequest $serviceRequest): Invoice
    {
        return DB::transaction(function () use ($serviceRequest) {
            $subscription = $serviceRequest->subscription()->with('generator')->firstOrFail();

            $dueDays = config('billing.invoice_due_days', 7);
            $finalAmount = (float) $serviceRequest->fee_amount;
            $currency = $serviceRequest->fee_currency;

            [$exchangeRate, $amountIls] = $this->exchangeRateService->toIls($finalAmount, $currency);

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'meter_reading_id' => null,
                'service_request_id' => $serviceRequest->id,
                'amount' => $finalAmount,
                'discount_amount' => 0,
                'discount_id' => null,
                'final_amount' => $finalAmount,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'final_amount_ils' => $amountIls,
                'due_date' => Carbon::now()->addDays($dueDays),
            ]);

            $this->createCommission($invoice, $subscription->generator->owner, $amountIls);

            SendInvoiceEmail::dispatch($invoice->id)->afterCommit();

            return $invoice->fresh(['commission', 'payments']);
        });
    }

    private function createCommission(Invoice $invoice, User $owner, float $amountIls): void
    {
        $rate = $this->commissionRateResolver->resolve($owner);

        PlatformCommission::create([
            'invoice_id' => $invoice->id,
            'owner_id' => $owner->id,
            'commission_rate' => $rate,
            'commission_amount' => Money::percentage($amountIls, $rate, 2),
        ]);
    }

    public function remainingBalance(Invoice $invoice): float
    {
        $paidIls = (float) $invoice->payments()->where('status', PaymentStatus::Paid)->sum('amount_ils');

        return Money::max(
            0.0,
            Money::sub((float) $invoice->final_amount_ils, $paidIls, 2),
            2
        );
    }

    /**
     * BUG-002: قبل هذا الإصلاح، كانت هذه الدالة تعمل `refresh()` بدون قفل
     * أي صف — و`ApprovePaymentAction` (على خلاف `CreatePaymentAction` و
     * `ProcessGatewayPaymentAction`، اللتين تقفلان صف الفاتورة بالفعل قبل
     * استدعاء هذه الدالة) كانت تقفل صف الـ Payment فقط، لا صف الـ Invoice.
     * دفعتان معتمدتان بالتوازي على نفس الفاتورة يقدر كل واحدة تحسب paidSum
     * من نسخة قديمة (قبل التزام الأخرى)، فتنتج حالة فاتورة غير صحيحة
     * (lost update).
     *
     * الإصلاح: قفل صف الفاتورة هنا مباشرة (`lockForUpdate`) بدل الاعتماد
     * على أن كل Caller يقفلها بنفسه مسبقًا — يضمن أن أي طريق حالي أو
     * مستقبلي يمرّ من هنا يحصل نفس الضمانة تلقائيًا. القفل المكرر من
     * الـ Callers اللي أصلًا يقفلون الفاتورة (نفس الصف، نفس الـ transaction)
     * غير ضار — مجرد إعادة تأكيد لقفل موجود أصلًا، ليس خطأ.
     */
    public function recalculateStatus(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $paidSum = (float) $invoice->payments()->where('status', PaymentStatus::Paid)->sum('amount_ils');
            $finalAmountIls = (float) $invoice->final_amount_ils;

            if ($invoice->status === InvoiceStatus::Cancelled) {
                return $invoice;
            }

            $newStatus = match (true) {
                Money::lessThanOrEqual($paidSum, 0.0, 2) => InvoiceStatus::Pending,
                Money::lessThan($paidSum, $finalAmountIls, 2) => InvoiceStatus::PartiallyPaid,
                default => InvoiceStatus::Paid,
            };

            $invoice->forceFill(['status' => $newStatus])->save();

            if ($newStatus === InvoiceStatus::Paid && $invoice->commission && $invoice->commission->status === PlatformCommissionStatus::Pending) {

                $invoice->commission->forceFill([
                    'status' => PlatformCommissionStatus::Earned,
                    'earned_at' => now(),
                ])->save();
            }

            if ($newStatus === InvoiceStatus::Paid) {
                InvoicePaid::dispatch($invoice);
            }

            return $invoice->fresh(['payments', 'commission']);
        });
    }

    public function markOverdueInvoices(): int
    {
        $graceDays = config('billing.overdue_grace_days', 3);
        $cutoff = Carbon::today()->subDays($graceDays);

        $count = 0;

        Invoice::whereIn('status', [InvoiceStatus::Pending, InvoiceStatus::PartiallyPaid])
            ->whereDate('due_date', '<', $cutoff)
            ->chunkById(200, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();
                    InvoiceOverdue::dispatch($invoice);
                    $count++;
                }
            });

        return $count;
    }

    public function availablePaymentMethods(Invoice $invoice): Collection
    {
        $invoice->loadMissing('subscription.generator');

        $ownerId = $invoice->subscription?->generator?->owner_id;

        if (! $ownerId) {
            return collect();
        }

        return PaymentMethod::query()
            ->where('user_id', $ownerId)
            ->whereIn('type', [
                PaymentMethodType::Wallet,
                PaymentMethodType::Bank,
                PaymentMethodType::Cash,
            ])
            ->get();
    }
}
