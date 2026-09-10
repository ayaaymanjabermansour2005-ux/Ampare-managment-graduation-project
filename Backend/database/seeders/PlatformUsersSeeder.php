<?php

namespace Database\Seeders;

use App\Enums\BeneficiaryType;
use App\Enums\CommissionMode;
use App\Enums\ComplaintStatus;
use App\Enums\FaultStatus;
use App\Enums\FuelType;
use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OfferStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Enums\Role as RoleEnum;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\SubscriberMeterStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianPaymentStatus;
use App\Enums\TechnicianStatus;
use App\Enums\TechnicianTaskStatus;
use App\Enums\TechnicianTaskType;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleRating;
use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Message;
use App\Models\MeterReading;
use App\Models\Neighborhood;
use App\Models\Offer;
use App\Models\OwnerRating;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\PlatformCommission;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\TechnicianRating;
use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * يبني مجموعة حسابات تجريبية واقعية (أسماء عربية متنوعة وغير مكررة، بيانات
 * مترابطة وحقيقية الشكل) بحيث تصبح كل شاشة بالمنصة قابلة للعرض ببيانات فعلية
 * بغض النظر عن الحساب المستخدم بتسجيل الدخول.
 *
 * لا يلمس هذا الـ Seeder بيانات RoleSeeder/PermissionSeeder/DatabaseSeeder
 * الحالية — يُضيف طبقة بيانات جديدة إضافة على ما هو موجود، ويعتمد على وجود
 * الأدوار/الأحياء/الخطط مسبقًا (يُستدعى بعدها بـ DatabaseSeeder::run()).
 */
class PlatformUsersSeeder extends Seeder
{
    /** كلمة المرور الموحّدة لكل الحسابات التجريبية بهذا الـ Seeder. */
    private const PASSWORD = 'Ampare@2026';

    /** التوزيع الأساسي المطلوب: 8 مالكين + 12 مشترك = 20 حسابًا. قابل للتعديل من هنا فقط. */
    private const OWNERS_COUNT = 8;

    private const SUBSCRIBERS_COUNT = 12;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('PlatformUsersSeeder ممنوع تنفيذه على بيئة الإنتاج.');
        }

        Model::unguarded(fn () => $this->seed());
    }

    private function seed(): void
    {
        $neighborhoods = Neighborhood::all()->values();
        $plans = Plan::all()->keyBy('code');
        $admin = User::where('email', 'admin@ampare.test')->firstOrFail();

        $owners = $this->seedOwners($neighborhoods, $plans);
        $this->backfillAyatGenerators($neighborhoods);
        $technicians = $this->seedExtraTechnicians($owners);
        $subscribers = $this->seedSubscribers($neighborhoods, $owners);
        $this->seedArticleEngagement($subscribers, $admin);

        $this->printCredentialsTable($owners, $subscribers, $technicians);
    }

    // ==================== المالكون ====================

    /**
     * @return array<int, array{user: User, generators: Collection<int, Generator>}>
     */
    private function seedOwners($neighborhoods, $plans): array
    {
        $ownersData = [
            ['name' => 'محمود أبو شمالة', 'email' => 'mahmoud.abushamala@example.test', 'phone' => '0599100101', 'plan' => 'pro', 'commission_mode' => CommissionMode::Fixed, 'commission_rate' => 5.00, 'generators' => [
                ['name' => 'مولد أبو شمالة الرئيسي', 'name_en' => 'Abu Shamala Main Generator', 'status' => GeneratorStatus::Active, 'price' => 0.55, 'currency' => 'ILS', 'capacity' => 60, 'fuel_type' => FuelType::Diesel],
            ]],
            ['name' => 'سناء الأغا', 'email' => 'sanaa.alagha@example.test', 'phone' => '0599100102', 'plan' => 'pro', 'commission_mode' => CommissionMode::Tiered, 'commission_rate' => null, 'generators' => [
                ['name' => 'مولد الأغا - النصر', 'name_en' => 'Al-Agha Generator - Al-Nasr', 'status' => GeneratorStatus::Active, 'price' => 0.45, 'currency' => 'ILS', 'capacity' => 40, 'fuel_type' => FuelType::Gas],
                ['name' => 'مولد الأغا - الاحتياطي', 'name_en' => 'Al-Agha Generator - Backup', 'status' => GeneratorStatus::Maintenance, 'price' => 0.45, 'currency' => 'ILS', 'capacity' => 25, 'fuel_type' => FuelType::Dual],
            ]],
            ['name' => 'خالد النجار', 'email' => 'khaled.alnajjar@example.test', 'phone' => '0569100103', 'plan' => 'enterprise', 'commission_mode' => CommissionMode::Fixed, 'commission_rate' => 4.00, 'generators' => [
                ['name' => 'مولد النجار للصناعات', 'name_en' => 'Al-Najjar Industrial Generator', 'status' => GeneratorStatus::Active, 'price' => 0.18, 'currency' => 'USD', 'capacity' => 120, 'fuel_type' => FuelType::Diesel],
            ]],
            ['name' => 'رنا أبو دقة', 'email' => 'rana.abudaqa@example.test', 'phone' => '0599100104', 'plan' => 'basic', 'commission_mode' => CommissionMode::Fixed, 'commission_rate' => 6.00, 'generators' => [
                ['name' => 'مولد أبو دقة - تل الهوى', 'name_en' => 'Abu Daqqa Generator - Tel Al-Hawa', 'status' => GeneratorStatus::PendingVerification, 'price' => 0.5, 'currency' => 'ILS', 'capacity' => 30, 'fuel_type' => FuelType::Petrol],
            ]],
            ['name' => 'إبراهيم شاهين', 'email' => 'ibrahim.shaheen@example.test', 'phone' => '0569100105', 'plan' => 'basic', 'commission_mode' => CommissionMode::Tiered, 'commission_rate' => null, 'generators' => [
                ['name' => 'مولد شاهين - الدرج', 'name_en' => 'Shaheen Generator - Al-Daraj', 'status' => GeneratorStatus::Active, 'price' => 0.48, 'currency' => 'ILS', 'capacity' => 35, 'fuel_type' => FuelType::Diesel],
            ]],
            ['name' => 'هبة الكحلوت', 'email' => 'heba.alkahlout@example.test', 'phone' => '0599100106', 'plan' => 'basic', 'commission_mode' => CommissionMode::Fixed, 'commission_rate' => 5.00, 'generators' => [
                ['name' => 'مولد الكحلوت - التفاح', 'name_en' => 'Al-Kahlout Generator - Al-Tuffah', 'status' => GeneratorStatus::Inactive, 'price' => 0.5, 'currency' => 'ILS', 'capacity' => 20, 'fuel_type' => FuelType::Gas],
            ]],
            ['name' => 'عمر ياسين', 'email' => 'omar.yassin@example.test', 'phone' => '0569100107', 'plan' => 'enterprise', 'commission_mode' => CommissionMode::Fixed, 'commission_rate' => 3.50, 'generators' => [
                ['name' => 'مولد ياسين الكبير - الصبرة', 'name_en' => 'Yassin Main Generator - Al-Sabra', 'status' => GeneratorStatus::Active, 'price' => 0.16, 'currency' => 'USD', 'capacity' => 150, 'fuel_type' => FuelType::Dual],
                ['name' => 'مولد ياسين الفرعي', 'name_en' => 'Yassin Secondary Generator', 'status' => GeneratorStatus::Active, 'price' => 0.5, 'currency' => 'ILS', 'capacity' => 45, 'fuel_type' => FuelType::Diesel],
            ]],
            // بدون أي مولد إطلاقًا — لاختبار Empty State بشاشات المالك (مولداتي/الفواتير/المشتركين...).
            ['name' => 'لينا زقّوت', 'email' => 'lina.zaqqout@example.test', 'phone' => '0599100108', 'plan' => 'basic', 'commission_mode' => CommissionMode::Tiered, 'commission_rate' => null, 'generators' => []],
        ];

        $ownersData = array_slice($ownersData, 0, self::OWNERS_COUNT);

        $result = [];

        foreach ($ownersData as $i => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'plan_id' => $plans[$data['plan']]->id ?? null,
                    'commission_mode' => $data['commission_mode'],
                    'commission_rate' => $data['commission_rate'],
                ]
            );

            if (! $user->hasRole(RoleEnum::GENERATOR_OWNER->value)) {
                $user->assignRole(RoleEnum::GENERATOR_OWNER->value);
            }

            $neighborhood = $neighborhoods[$i % max($neighborhoods->count(), 1)];
            $location = Location::firstOrCreate(
                ['city' => 'غزة', 'neighborhood_id' => $neighborhood->id, 'address' => "شارع رئيسي - {$neighborhood->name}"],
                ['latitude' => 31.5 + ($i * 0.01), 'longitude' => 34.45 + ($i * 0.01)]
            );

            $generators = collect();
            foreach ($data['generators'] as $genData) {
                $generator = Generator::firstOrCreate(
                    ['owner_id' => $user->id, 'name' => $genData['name']],
                    [
                        'name_en' => $genData['name_en'],
                        'price_per_kw' => $genData['price'],
                        'currency' => $genData['currency'],
                        'capacity_kw' => $genData['capacity'],
                        'fuel_type' => $genData['fuel_type']->value,
                        'location_id' => $location->id,
                        'status' => $genData['status'],
                        'operating_schedule' => '24h',
                    ]
                );
                $generators->push($generator);

                // LIVE-SCHEDULE-demo-data: /live-schedule (public landing widget +
                // page) had a fully-built backend and frontend but zero seeded
                // GeneratorSchedule rows anywhere — every neighborhood filter
                // (including "all neighborhoods") returned empty, not because of
                // a missing filter option. updateOrCreate (not firstOrCreate) so
                // the "active now" window stays genuinely live no matter which
                // day this seeder is re-run on, instead of drifting stale.
                if ($genData['status'] === GeneratorStatus::Active) {
                    GeneratorSchedule::updateOrCreate(
                        ['generator_id' => $generator->id, 'note' => 'عرض توضيحي - نشط الآن'],
                        ['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(3), 'created_by' => $user->id]
                    );
                    GeneratorSchedule::updateOrCreate(
                        ['generator_id' => $generator->id, 'note' => 'عرض توضيحي - قادم'],
                        ['starts_at' => now()->addHours(5), 'ends_at' => now()->addHours(8), 'created_by' => $user->id]
                    );
                }
            }

            PaymentMethod::firstOrCreate(
                ['user_id' => $user->id, 'type' => PaymentMethodType::Wallet],
                ['is_default' => true, 'currency' => 'ILS']
            );

            // عروض تجريبية لكل مالك لديه مولد فعّال (تنويع Active/Cancelled).
            if ($generators->isNotEmpty()) {
                Offer::firstOrCreate(
                    ['owner_id' => $user->id, 'title' => "خصم ترحيبي - {$data['name']}"],
                    [
                        'description' => 'خصم لكل مشترك جديد خلال أول شهر.',
                        'discount_type' => 'percentage',
                        'discount_value' => 5 + ($i % 3) * 5,
                        'target_mode' => 'all',
                        'start_date' => now()->subDays(10),
                        'end_date' => now()->addDays(20),
                        'status' => $i % 4 === 0 ? OfferStatus::Cancelled : OfferStatus::Active,
                    ]
                );
            }

            // عمولة منصة تجريبية شهرية للمالكين النشطين (لشاشة تقارير الأدمن/المالك).
            if ($generators->isNotEmpty()) {
                $latestInvoice = Invoice::whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $user->id))->first();
                if ($latestInvoice) {
                    PlatformCommission::firstOrCreate(
                        ['invoice_id' => $latestInvoice->id, 'owner_id' => $user->id],
                        ['commission_rate' => 5.00, 'commission_amount' => round($latestInvoice->final_amount * 0.05, 2), 'status' => 'pending']
                    );
                }
            }

            $result[] = ['user' => $user, 'generators' => $generators];
        }

        return $result;
    }

    /**
     * AYAT-GENERATORS-BACKFILL: قبل هالسيدر كان في مولدين بنفس الاسم "مولد أيات"
     * تحت خالد النجار انعملوا يدويًا من واجهة الأدمن (مش من generators الأعلى)،
     * فطلعوا بدون موقع/مدينة، والمولد الفعّال منهم بدون أي مشترك (فتظهر أعمدة
     * المدينة/المشتركين/الإيراد فاضية بالجدول). ما ضفناهم لمصفوفة generators
     * بالأعلى لأنو firstOrCreate هناك بيتعرّف على المولد بـ owner_id+name، واسم
     * الاثنين متطابق فما بيفرّق بينهم. بدلها، نكمّل بياناتهم هون بالبحث عنهم
     * مباشرة بالاسم تحت نفس المالك.
     */
    private function backfillAyatGenerators(Collection $neighborhoods): void
    {
        $owner = User::where('email', 'khaled.alnajjar@example.test')->first();
        if (! $owner) {
            return;
        }

        $ayatGenerators = Generator::where('owner_id', $owner->id)->where('name', 'مولد أيات')->get();
        $neighborhood = $neighborhoods->first();
        if ($ayatGenerators->isEmpty() || ! $neighborhood) {
            return;
        }

        $location = Location::firstOrCreate(
            ['city' => 'غزة', 'neighborhood_id' => $neighborhood->id, 'address' => "شارع أيات - {$neighborhood->name}"],
            ['latitude' => 31.52, 'longitude' => 34.46]
        );

        $ayatGenerators->whereNull('location_id')->each(
            fn (Generator $g) => $g->update(['location_id' => $location->id])
        );

        $activeGenerator = $ayatGenerators->firstWhere('status', GeneratorStatus::Active->value);
        if (! $activeGenerator || $activeGenerator->subscriptions()->exists()) {
            return;
        }

        $subUser = User::updateOrCreate(
            ['email' => 'wael.zarab@example.test'],
            [
                'name' => 'وائل زعرب',
                'phone' => '0599200313',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
        if (! $subUser->hasRole(RoleEnum::SUBSCRIBER->value)) {
            $subUser->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        $subscriber = Subscriber::firstOrCreate(
            ['user_id' => $subUser->id],
            [
                'neighborhood_id' => $neighborhood->id,
                'address' => "منزل رقم 30 - {$neighborhood->name}",
                'joined_at' => now()->subDays(20),
                'beneficiary_type' => BeneficiaryType::Normal,
            ]
        );

        $meter = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'PU-5001'],
            [
                'subscriber_id' => $subscriber->id,
                'property_label' => 'المنزل الرئيسي',
                'status' => SubscriberMeterStatus::Active,
            ]
        );

        $subscription = Subscription::firstOrCreate(
            ['subscriber_meter_id' => $meter->id, 'generator_id' => $activeGenerator->id, 'schedule' => 'day'],
            [
                'agreed_price_per_kw' => $activeGenerator->price_per_kw,
                'currency' => $activeGenerator->currency,
                'requested_capacity_kw' => 15,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(20),
                'status' => SubscriptionStatus::Active,
            ]
        );

        $reading = MeterReading::firstOrCreate(
            ['subscription_id' => $subscription->id, 'reading_date' => now()->subDays(5)->toDateString()],
            ['previous_reading' => 500, 'current_reading' => 540, 'created_by' => $owner->id]
        );

        $amount = 32.5;
        $invoice = Invoice::firstOrCreate(
            ['subscription_id' => $subscription->id, 'due_date' => now()->addDays(7)->toDateString()],
            [
                'meter_reading_id' => $reading->id,
                'amount' => $amount,
                'discount_amount' => 0,
                'discount_id' => null,
                'final_amount' => $amount,
                'currency' => $activeGenerator->currency,
                'exchange_rate' => $activeGenerator->currency->value === 'ILS' ? null : 3.6,
                'final_amount_ils' => $activeGenerator->currency->value === 'USD' ? round($amount * 3.6, 2) : $amount,
                'status' => InvoiceStatus::Paid,
            ]
        );

        Payment::firstOrCreate(
            ['transaction_reference' => 'AYAT-PAY-0001'],
            [
                'invoice_id' => $invoice->id,
                'source' => 'subscriber',
                'amount' => $amount,
                'currency' => $activeGenerator->currency,
                'exchange_rate' => $activeGenerator->currency->value === 'ILS' ? null : 3.6,
                'amount_ils' => $activeGenerator->currency->value === 'USD' ? round($amount * 3.6, 2) : $amount,
                'status' => PaymentStatus::Paid,
                'processed_by' => $owner->id,
                'paid_at' => now()->subDays(3),
                'reviewed_by' => $owner->id,
                'reviewed_at' => now()->subDays(3),
            ]
        );
    }

    // ==================== فنيّون إضافيون ====================

    /**
     * @param  array<int, array{user: User, generators: Collection}>  $owners
     * @return array<int, User>
     */
    private function seedExtraTechnicians(array $owners): array
    {
        $techniciansData = [
            ['name' => 'ماجد أبو عمرة', 'email' => 'majed.abuamra@example.test', 'phone' => '0569100201', 'owner_index' => 0, 'status' => TechnicianStatus::Active],
            ['name' => 'وائل الكرد', 'email' => 'wael.alkurd@example.test', 'phone' => '0599100202', 'owner_index' => 2, 'status' => TechnicianStatus::Active],
            ['name' => 'إسراء دياب', 'email' => 'israa.diab@example.test', 'phone' => '0569100203', 'owner_index' => 6, 'status' => TechnicianStatus::Suspended],
        ];

        $result = [];

        foreach ($techniciansData as $data) {
            $techUser = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            if (! $techUser->hasRole(RoleEnum::TECHNICIAN->value)) {
                $techUser->assignRole(RoleEnum::TECHNICIAN->value);
            }

            $owner = $owners[$data['owner_index']]['user'];

            $technician = Technician::firstOrCreate(
                ['user_id' => $techUser->id],
                ['owner_id' => $owner->id, 'status' => $data['status'], 'notes' => 'فني تجريبي ضمن مجموعة الحسابات الموسّعة.']
            );

            $generators = $owners[$data['owner_index']]['generators'];
            if ($generators->isNotEmpty()) {
                $technician->generators()->syncWithoutDetaching([$generators->first()->id]);

                // أمر شغل + دفعة أجرة لكل فني (تنويع الحالات عبر الفنيين الثلاثة).
                $task = TechnicianTask::firstOrCreate(
                    ['generator_id' => $generators->first()->id, 'technician_id' => $technician->id, 'type' => TechnicianTaskType::GeneralMaintenance, 'status' => TechnicianTaskStatus::Submitted],
                    [
                        'requested_by' => $owner->id,
                        'assigned_by' => $owner->id,
                        'reviewer_role' => 'owner',
                        'instructions' => 'فحص دوري شامل للمولد.',
                        'assigned_at' => now()->subDays(3),
                        'started_at' => now()->subDays(2),
                        'submitted_at' => now()->subDay(),
                        'completion_notes' => 'تم الفحص والصيانة الوقائية.',
                    ]
                );

                TechnicianPayment::firstOrCreate(
                    ['technician_id' => $technician->id, 'owner_id' => $owner->id, 'status' => TechnicianPaymentStatus::Pending],
                    ['amount' => 200, 'currency' => 'ILS', 'note' => 'أجرة الصيانة الدورية.', 'created_by' => $owner->id]
                );

                TechnicianRating::firstOrCreate(
                    ['technician_task_id' => $task->id, 'technician_id' => $technician->id, 'rated_by' => $owner->id],
                    ['rating' => 4, 'comment' => 'التزام جيد بالمواعيد.']
                );
            }

            $result[] = $techUser;
        }

        return $result;
    }

    // ==================== المشتركون ====================

    /**
     * @param  array<int, array{user: User, generators: Collection}>  $owners
     * @return array<int, User>
     */
    private function seedSubscribers($neighborhoods, array $owners): array
    {
        // مالكون لديهم مولد فعّال واحد على الأقل يمكن الاشتراك به.
        $ownersWithGenerators = array_values(array_filter($owners, fn ($o) => $o['generators']->isNotEmpty()));

        $subscribersData = [
            ['name' => 'أحمد الحلو', 'email' => 'ahmad.alhilu@example.test', 'phone' => '0599200301'],
            ['name' => 'مريم أبو عيطة', 'email' => 'mariam.abuayta@example.test', 'phone' => '0569200302'],
            ['name' => 'يوسف الترك', 'email' => 'yousef.alturk@example.test', 'phone' => '0599200303'],
            ['name' => 'دعاء الشوا', 'email' => 'duaa.alshawa@example.test', 'phone' => '0569200304'],
            ['name' => 'سامر بركة', 'email' => 'samer.baraka@example.test', 'phone' => '0599200305'],
            ['name' => 'نور الدين حماد', 'email' => 'noureddin.hammad@example.test', 'phone' => '0569200306'],
            ['name' => 'آية الرنتيسي', 'email' => 'aya.alrantisi@example.test', 'phone' => '0599200307'],
            ['name' => 'زياد أبو مصطفى', 'email' => 'ziad.abumustafa@example.test', 'phone' => '0569200308'],
            ['name' => 'غادة السقا', 'email' => 'ghada.alsaqqa@example.test', 'phone' => '0599200309'],
            ['name' => 'طارق قنديل', 'email' => 'tareq.qandil@example.test', 'phone' => '0569200310'],
            ['name' => 'شيماء الحداد', 'email' => 'shaimaa.alhaddad@example.test', 'phone' => '0599200311'],
            ['name' => 'باسل عودة', 'email' => 'basel.odeh@example.test', 'phone' => '0569200312'],
        ];

        $subscribersData = array_slice($subscribersData, 0, self::SUBSCRIBERS_COUNT);

        // تدوير حالات الاشتراك/الفاتورة/الدفع/الشكوى/العطل بحيث تُغطّى كل القيم الممكنة عبر الـ 12 مشترك.
        $subscriptionStatuses = [
            SubscriptionStatus::Active, SubscriptionStatus::Active, SubscriptionStatus::Pending,
            SubscriptionStatus::Active, SubscriptionStatus::Suspended, SubscriptionStatus::Active,
            SubscriptionStatus::Cancelled, SubscriptionStatus::Active, SubscriptionStatus::Rejected,
            SubscriptionStatus::Active, SubscriptionStatus::Pending, SubscriptionStatus::Active,
        ];
        $invoiceStatuses = [
            InvoiceStatus::Paid, InvoiceStatus::Pending, InvoiceStatus::Overdue,
            InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid, InvoiceStatus::Cancelled,
            InvoiceStatus::Overdue, InvoiceStatus::Paid, InvoiceStatus::Pending,
            InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid, InvoiceStatus::Overdue,
        ];
        $beneficiaryTypes = [BeneficiaryType::Normal, BeneficiaryType::Special];

        $result = [];

        foreach ($subscribersData as $i => $data) {
            $subUser = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            if (! $subUser->hasRole(RoleEnum::SUBSCRIBER->value)) {
                $subUser->assignRole(RoleEnum::SUBSCRIBER->value);
            }

            $neighborhood = $neighborhoods[$i % max($neighborhoods->count(), 1)];

            $subscriber = Subscriber::firstOrCreate(
                ['user_id' => $subUser->id],
                [
                    'neighborhood_id' => $neighborhood->id,
                    'address' => 'منزل رقم '.(10 + $i)." - {$neighborhood->name}",
                    'joined_at' => now()->subDays(60 - $i * 3),
                    'beneficiary_type' => $beneficiaryTypes[$i % 2],
                ]
            );

            $meter = SubscriberMeter::firstOrCreate(
                ['meter_number' => sprintf('PU-%04d', 4000 + $i)],
                [
                    'subscriber_id' => $subscriber->id,
                    'property_label' => 'المنزل الرئيسي',
                    'status' => SubscriberMeterStatus::Active,
                ]
            );

            if (empty($ownersWithGenerators)) {
                $result[] = $subUser;

                continue;
            }

            $ownerEntry = $ownersWithGenerators[$i % count($ownersWithGenerators)];
            $generator = $ownerEntry['generators']->first();
            $owner = $ownerEntry['user'];

            $subStatus = $subscriptionStatuses[$i % count($subscriptionStatuses)];

            $subscription = Subscription::firstOrCreate(
                ['subscriber_meter_id' => $meter->id, 'generator_id' => $generator->id, 'schedule' => $i % 2 === 0 ? 'day' : 'night'],
                [
                    'agreed_price_per_kw' => $generator->price_per_kw,
                    'currency' => $generator->currency,
                    'requested_capacity_kw' => 10 + ($i % 5) * 5,
                    'contract_type' => $i % 3 === 0 ? 'commercial' : 'residential',
                    'start_date' => now()->subDays(45 - $i),
                    'status' => $subStatus,
                ]
            );

            // قراءة عداد + فاتورة بحالة مُتنوّعة لكل مشترك (تشمل صراحة: متأخرة/جزئية/ملغاة/مدفوعة/معلّقة).
            $reading = MeterReading::firstOrCreate(
                ['subscription_id' => $subscription->id, 'reading_date' => now()->subDays(15 - ($i % 10))->toDateString()],
                ['previous_reading' => 800 + $i * 10, 'current_reading' => 840 + $i * 10, 'created_by' => $owner->id]
            );

            $invStatus = $invoiceStatuses[$i % count($invoiceStatuses)];
            $amount = 15 + ($i % 6) * 3.5;

            $invoice = Invoice::firstOrCreate(
                ['subscription_id' => $subscription->id, 'due_date' => now()->addDays($invStatus === InvoiceStatus::Overdue ? -10 : 7)->toDateString()],
                [
                    'meter_reading_id' => $reading->id,
                    'amount' => $amount,
                    'discount_amount' => 0,
                    'discount_id' => null,
                    'final_amount' => $amount,
                    'currency' => $generator->currency,
                    'exchange_rate' => $generator->currency->value === 'ILS' ? null : 3.6,
                    'final_amount_ils' => $generator->currency->value === 'USD' ? round($amount * 3.6, 2) : $amount,
                    'status' => $invStatus,
                ]
            );

            if (in_array($invStatus, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid], true)) {
                // SEEDER-IDEMPOTENCY: نفس إصلاح DEMO-PAY-0001/0002 بـ DatabaseSeeder.php —
                // $invoice تُبحَث بمفتاح فيه now()->addDays(...)، تاريخ متحرك يتغيّر كل يوم،
                // فيتغيّر invoice_id بكل تشغيل بيوم مختلف. البحث هون بـ transaction_reference
                // لوحده (فريد عالميًا بالفعل) يمنع محاولة إدخال نفس المرجع مرتين.
                Payment::firstOrCreate(
                    ['transaction_reference' => 'PU-PAY-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                    [
                        'invoice_id' => $invoice->id,
                        'source' => 'subscriber',
                        'amount' => $invStatus === InvoiceStatus::PartiallyPaid ? round($amount * 0.5, 2) : $amount,
                        'currency' => $generator->currency,
                        'exchange_rate' => $generator->currency->value === 'ILS' ? null : 3.6,
                        'amount_ils' => $generator->currency->value === 'USD' ? round($amount * 3.6, 2) : $amount,
                        'status' => PaymentStatus::Paid,
                        'processed_by' => $owner->id,
                        'paid_at' => now()->subDays(3),
                        'reviewed_by' => $owner->id,
                        'reviewed_at' => now()->subDays(3),
                    ]
                );

                OwnerRating::firstOrCreate(
                    ['subscription_id' => $subscription->id, 'owner_id' => $owner->id, 'rated_by' => $subUser->id],
                    ['rating' => 4 + ($i % 2), 'comment' => 'تعامل ممتاز واستجابة سريعة.']
                );
            }

            // شكاوى وأعطال موزّعة على جزء من المشتركين فقط (لا كل الشاشات تحتاج بيانات من كل مشترك).
            if ($i % 3 === 0) {
                Complaint::firstOrCreate(
                    ['submitted_by' => $subUser->id, 'subject' => "استفسار بخصوص فاتورة {$data['name']}"],
                    [
                        'description' => 'الفاتورة الأخيرة أعلى من المعتاد، بحاجة توضيح.',
                        'status' => [ComplaintStatus::Pending, ComplaintStatus::InProgress, ComplaintStatus::WaitingSubscriber, ComplaintStatus::Resolved][($i / 3) % 4],
                    ]
                );
            }

            if ($i % 4 === 1) {
                Fault::firstOrCreate(
                    ['generator_id' => $generator->id, 'title' => "عطل مُبلَّغ من {$data['name']}"],
                    [
                        'reported_by' => $subUser->id,
                        'source' => 'subscriber_report',
                        'description' => 'انخفاض ملحوظ بالجهد الكهربائي خلال ساعات المساء.',
                        'priority' => ['low', 'medium', 'high', 'critical'][$i % 4],
                        'reported_at' => now()->subDays(4),
                        'status' => FaultStatus::PendingVerification,
                    ]
                );
            }

            // طلب خدمة إضافية لبعض المشتركين (سيناريو حافة: طلب مرفوض).
            if ($i % 5 === 4) {
                SubscriptionServiceRequest::firstOrCreate(
                    ['subscription_id' => $subscription->id, 'requested_by' => $subUser->id, 'request_type' => ServiceRequestType::ExtraCapacity],
                    [
                        'event_type' => 'wedding',
                        'description' => 'زيادة مؤقتة بالسعة بمناسبة عائلية.',
                        'extra_capacity_kw' => 5,
                        'starts_at' => now()->addDays(2),
                        'ends_at' => now()->addDays(3),
                        'status' => ServiceRequestStatus::Rejected,
                    ]
                );
            }

            // محادثة دعم بين المشترك والمالك لأول 4 مشتركين فقط (كافية لتغطية شاشة الرسائل).
            if ($i < 4) {
                $conversation = Conversation::firstOrCreate([
                    'user1_id' => min($owner->id, $subUser->id),
                    'user2_id' => max($owner->id, $subUser->id),
                ]);

                Message::firstOrCreate(
                    ['conversation_id' => $conversation->id, 'sender_id' => $subUser->id, 'message_text' => 'مرحبًا، متى موعد قراءة العداد القادمة؟'],
                    ['is_read' => true]
                );
                Message::firstOrCreate(
                    ['conversation_id' => $conversation->id, 'sender_id' => $owner->id, 'message_text' => 'أهلًا، الفني هيمر عليكم خلال يومين إن شاء الله.'],
                    ['is_read' => false]
                );
            }

            $result[] = $subUser;
        }

        return $result;
    }

    // ==================== تفاعل على المقالات (تعليقات + تقييمات) ====================

    /** @param array<int, User> $subscribers */
    private function seedArticleEngagement(array $subscribers, User $admin): void
    {
        $article = Article::where('is_published', true)->first();
        if (! $article || empty($subscribers)) {
            return;
        }

        ArticleComment::firstOrCreate(
            ['article_id' => $article->id, 'email' => $subscribers[0]->email, 'comment' => 'مقال مفيد جدًا، استفدت منه بمتابعة اشتراكي.'],
            ['name' => $subscribers[0]->name, 'status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()->subDay()]
        );

        if (isset($subscribers[1])) {
            ArticleComment::firstOrCreate(
                ['article_id' => $article->id, 'email' => $subscribers[1]->email, 'comment' => 'هل يوجد شرح فيديو لنفس الخطوات؟'],
                ['name' => $subscribers[1]->name, 'status' => 'pending']
            );
        }

        ArticleRating::firstOrCreate(
            ['article_id' => $article->id, 'visitor_hash' => hash('sha256', $subscribers[0]->email)],
            ['rating' => 5]
        );
    }

    // ==================== طباعة بيانات الدخول ====================

    /**
     * @param  array<int, array{user: User, generators: Collection}>  $owners
     * @param  array<int, User>  $subscribers
     * @param  array<int, User>  $technicians
     */
    private function printCredentialsTable(array $owners, array $subscribers, array $technicians): void
    {
        if (! $this->command) {
            return;
        }

        $rows = [];

        foreach ($owners as $o) {
            $rows[] = [$o['user']->name, $o['user']->email, 'generator_owner', self::PASSWORD];
        }
        foreach ($subscribers as $u) {
            $rows[] = [$u->name, $u->email, 'subscriber', self::PASSWORD];
        }
        foreach ($technicians as $u) {
            $rows[] = [$u->name, $u->email, 'technician', self::PASSWORD];
        }
        $rows[] = ['(الأدمن الوحيد)', 'admin@ampare.test', 'admin', 'راجع DatabaseSeeder'];

        $this->command->info('==================== بيانات دخول الحسابات التجريبية (PlatformUsersSeeder) ====================');
        $this->command->table(['الاسم', 'البريد الإلكتروني', 'الدور', 'كلمة المرور'], $rows);
    }
}
