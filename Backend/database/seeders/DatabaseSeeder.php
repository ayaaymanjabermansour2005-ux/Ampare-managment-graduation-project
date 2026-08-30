<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodType;
use App\Enums\Role as RoleEnum;
use App\Enums\SubscriptionMeterTransferStatus;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\MeterReading;
use App\Models\Neighborhood;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\OwnerApplication;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            NeighborhoodSeeder::class,
            PlanSeeder::class,
        ]);

        Model::unguarded(fn() => $this->seedCoreData());
        $this->call(DemoAccountsSeeder::class);
        $this->call(PlatformUsersSeeder::class);
    }

    /**
     * كلمات مرور الحسابات التجريبية المولَّدة بهذا التشغيل الحالي فقط —
     * {email => plaintext}. تُستخدم فقط لطباعة جدول اعتماد محلي مرة واحدة
     * (printSeedCredentials)، ولا تُخزَّن ولا تُسجَّل بأي مكان آخر.
     *
     * @var array<string, string>
     */
    private array $generatedCredentials = [];

    private function seedCoreData(): void
    {
        // SEC-006: كانت هذه الدالة تُنشئ 7 حسابات (owner1/2, subscriber1/2/3,
        // technician1/2) بكلمة مرور واحدة ثابتة ومشتركة ('password') بدون أي
        // حارس بيئة — بيانات تجريبية بالكامل (Owner One/Two، فواتير وهمية...)
        // لا علاقة لها بأي سيناريو إنتاج حقيقي. نفس الحارس المستخدم أصلًا في
        // PlatformUsersSeeder.php:73-75.
        if (app()->environment('production')) {
            throw new \RuntimeException('DatabaseSeeder::seedCoreData ممنوع تنفيذه على بيئة الإنتاج.');
        }

        // ==================== المستخدمون ====================
        $admin = User::where('email', 'admin@ampare.test')->firstOrFail();

        $owner1 = User::firstOrCreate(
            ['email' => 'owner1@ampare.test'],
            [
                'name' => 'Owner One',
                'password' => Hash::make($this->demoPassword('owner1@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $owner1->hasRole(RoleEnum::GENERATOR_OWNER->value)) {
            $owner1->assignRole(RoleEnum::GENERATOR_OWNER->value);
        }

        $owner2 = User::firstOrCreate(
            ['email' => 'owner2@ampare.test'],
            [
                'name' => 'Owner Two',
                'password' => Hash::make($this->demoPassword('owner2@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $owner2->hasRole(RoleEnum::GENERATOR_OWNER->value)) {
            $owner2->assignRole(RoleEnum::GENERATOR_OWNER->value);
        }

        $subUser1 = User::firstOrCreate(
            ['email' => 'subscriber1@ampare.test'],
            [
                'name' => 'Subscriber One',
                'password' => Hash::make($this->demoPassword('subscriber1@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $subUser1->hasRole(RoleEnum::SUBSCRIBER->value)) {
            $subUser1->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        $subUser2 = User::firstOrCreate(
            ['email' => 'subscriber2@ampare.test'],
            [
                'name' => 'Subscriber Two',
                'password' => Hash::make($this->demoPassword('subscriber2@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $subUser2->hasRole(RoleEnum::SUBSCRIBER->value)) {
            $subUser2->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        $subUser3 = User::firstOrCreate(
            ['email' => 'subscriber3@ampare.test'],
            [
                'name' => 'Subscriber Three',
                'password' => Hash::make($this->demoPassword('subscriber3@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $subUser3->hasRole(RoleEnum::SUBSCRIBER->value)) {
            $subUser3->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        // ==================== الأحياء ====================
        $neighborhoodRimal = Neighborhood::firstOrCreate(['name' => 'الرمال']);
        $neighborhoodShujaiya = Neighborhood::firstOrCreate(['name' => 'الشجاعية']);
        $neighborhoodZaytoun = Neighborhood::firstOrCreate(['name' => 'الزيتون']);

        // ==================== المواقع ====================
        $locationRimal = Location::firstOrCreate(
            [
                'city' => 'غزة',
                'neighborhood_id' => $neighborhoodRimal->id,
            ],
            [
                'address' => 'حي الرمال - غزة',
                'latitude' => null,
                'longitude' => null,
            ]
        );

        $locationShujaiya = Location::firstOrCreate(
            [
                'city' => 'غزة',
                'neighborhood_id' => $neighborhoodShujaiya->id,
            ],
            [
                'address' => 'حي الشجاعية - غزة',
                'latitude' => null,
                'longitude' => null,
            ]
        );

        // ==================== المولدات ====================
        $generatorA = Generator::firstOrCreate(
            [
                'owner_id' => $owner1->id,
                'name' => 'مولد الرمال الرئيسي',
            ],
            [
                'price_per_kw' => 0.50,
                'currency' => 'ILS',
                'capacity_kw' => 50,
                'location_id' => $locationRimal->id,
                'status' => 'active',
                'operating_schedule' => '24h',
            ]
        );

        $generatorB = Generator::firstOrCreate(
            [
                'owner_id' => $owner2->id,
                'name' => 'مولد الشجاعية',
            ],
            [
                'price_per_kw' => 0.15,
                'currency' => 'USD',
                'capacity_kw' => 100,
                'location_id' => $locationShujaiya->id,
                'status' => 'active',
                'operating_schedule' => '24h',
            ]
        );

        // ==================== الفنيون ====================
        $technicianUser1 = User::firstOrCreate(
            ['email' => 'technician1@ampare.test'],
            [
                'name' => 'Technician One',
                'password' => Hash::make($this->demoPassword('technician1@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $technicianUser1->hasRole(RoleEnum::TECHNICIAN->value)) {
            $technicianUser1->assignRole(RoleEnum::TECHNICIAN->value);
        }

        $technician1 = Technician::firstOrCreate(
            ['user_id' => $technicianUser1->id],
            ['owner_id' => $owner1->id, 'status' => 'active', 'notes' => 'فني تجريبي لصيانة مولد الرمال.']
        );

        $technicianUser2 = User::firstOrCreate(
            ['email' => 'technician2@ampare.test'],
            [
                'name' => 'Technician Two',
                'password' => Hash::make($this->demoPassword('technician2@ampare.test')),
                'email_verified_at' => now(),
            ]
        );
        if (! $technicianUser2->hasRole(RoleEnum::TECHNICIAN->value)) {
            $technicianUser2->assignRole(RoleEnum::TECHNICIAN->value);
        }

        $technician2 = Technician::firstOrCreate(
            ['user_id' => $technicianUser2->id],
            ['owner_id' => $owner1->id, 'status' => 'active', 'notes' => 'فني احتياطي.']
        );

        $technician1->generators()->syncWithoutDetaching([$generatorA->id]);

        // ==================== أوامر شغل تجريبية للفني ====================
        TechnicianTask::firstOrCreate(
            ['generator_id' => $generatorA->id, 'technician_id' => $technician1->id, 'type' => 'general_maintenance', 'status' => 'in_progress'],
            [
                'requested_by' => $owner1->id,
                'assigned_by' => $owner1->id,
                'reviewer_role' => 'owner',
                'instructions' => 'فحص دوري لمولد الرمال الرئيسي.',
                'assigned_at' => now()->subDays(2),
                'started_at' => now()->subDay(),
            ]
        );

        TechnicianTask::firstOrCreate(
            ['generator_id' => $generatorA->id, 'technician_id' => $technician1->id, 'type' => 'wiring_maintenance', 'status' => 'submitted'],
            [
                'requested_by' => $owner1->id,
                'assigned_by' => $owner1->id,
                'reviewer_role' => 'owner',
                'instructions' => 'إصلاح تمديدات كهربائية تالفة.',
                'assigned_at' => now()->subDays(4),
                'started_at' => now()->subDays(3),
                'submitted_at' => now()->subDay(),
                'completion_notes' => 'تم استبدال الكابلات التالفة.',
            ]
        );

        TechnicianTask::firstOrCreate(
            ['generator_id' => $generatorA->id, 'technician_id' => $technician1->id, 'type' => 'fault_repair', 'status' => 'pending'],
            [
                'requested_by' => $owner1->id,
                'instructions' => 'بلاغ عطل بحاجة لجدولة زيارة.',
            ]
        );

        // ==================== دفعات الفني التجريبية ====================
        TechnicianPayment::firstOrCreate(
            ['technician_id' => $technician1->id, 'owner_id' => $owner1->id, 'status' => 'pending'],
            ['amount' => 250, 'currency' => 'ILS', 'note' => 'أجرة أسبوعية.', 'created_by' => $owner1->id]
        );

        TechnicianPayment::firstOrCreate(
            ['technician_id' => $technician1->id, 'owner_id' => $owner1->id, 'status' => 'approved'],
            [
                'amount' => 300, 'currency' => 'ILS', 'note' => 'أجرة الأسبوع الماضي.',
                'created_by' => $owner1->id, 'reviewed_by' => $technicianUser1->id, 'reviewed_at' => now()->subDays(5),
            ]
        );

        TechnicianPayment::firstOrCreate(
            ['technician_id' => $technician1->id, 'owner_id' => $owner1->id, 'status' => 'rejected'],
            [
                'amount' => 120, 'currency' => 'ILS', 'note' => 'قيمة غير مكتملة.',
                'created_by' => $owner1->id, 'reviewed_by' => $technicianUser1->id, 'reviewed_at' => now()->subDays(10),
                'rejection_reason' => 'المبلغ لا يطابق ساعات العمل الفعلية.',
            ]
        );

        // ==================== محادثة الفني مع المالك ====================
        $technicianConversation = Conversation::firstOrCreate([
            'user1_id' => min($owner1->id, $technicianUser1->id),
            'user2_id' => max($owner1->id, $technicianUser1->id),
        ]);

        Message::firstOrCreate(
            ['conversation_id' => $technicianConversation->id, 'sender_id' => $owner1->id, 'message_text' => 'أهلاً، رجاءً راجع مولد الرمال اليوم.'],
            ['is_read' => true]
        );

        Message::firstOrCreate(
            ['conversation_id' => $technicianConversation->id, 'sender_id' => $technicianUser1->id, 'message_text' => 'تمام، بكون هناك خلال ساعة.'],
            ['is_read' => false]
        );

        // ==================== ملفات المشتركين ====================
        $subscriber1 = Subscriber::firstOrCreate(
            ['user_id' => $subUser1->id],
            [
                'neighborhood_id' => $neighborhoodRimal->id,
                'address' => 'شارع الجلاء',
                'joined_at' => now(),
            ]
        );

        $subscriber2 = Subscriber::firstOrCreate(
            ['user_id' => $subUser2->id],
            [
                'neighborhood_id' => $neighborhoodShujaiya->id,
                'address' => 'شارع الوحدة',
                'joined_at' => now(),
            ]
        );

        $subscriber3 = Subscriber::firstOrCreate(
            ['user_id' => $subUser3->id],
            [
                'neighborhood_id' => $neighborhoodZaytoun->id,
                'address' => 'شارع المزارع',
                'joined_at' => now(),
            ]
        );

        // ==================== العدادات ====================
        $meter1001 = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'M-1001'],
            [
                'subscriber_id' => $subscriber1->id,
                'property_label' => 'منزل الرمال',
                'status' => 'active',
            ]
        );

        $meter1002 = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'M-1002'],
            [
                'subscriber_id' => $subscriber1->id,
                'property_label' => 'محل الشجاعية',
                'status' => 'active',
            ]
        );

        $meter2001 = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'M-2001'],
            [
                'subscriber_id' => $subscriber2->id,
                'property_label' => 'شقة الطابق الثاني',
                'status' => 'active',
            ]
        );

        $meter3001 = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'M-3001'],
            [
                'subscriber_id' => $subscriber3->id,
                'property_label' => 'المزرعة',
                'status' => 'active',
            ]
        );

        // ==================== الاشتراكات (العقود) ====================
        $subscription1 = Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter1001->id,
                'generator_id' => $generatorA->id,
                'schedule' => 'day',
            ],
            [
                'agreed_price_per_kw' => $generatorA->price_per_kw,
                'currency' => $generatorA->currency,
                'requested_capacity_kw' => 20,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(30),
                'status' => 'active',
            ]
        );

        // ==================== قراءات العدادات ====================
        $reading = MeterReading::firstOrCreate(
            [
                'subscription_id' => $subscription1->id,
                'reading_date' => now()->toDateString(),
            ],
            [
                'previous_reading' => 1000,
                'current_reading' => 1020,
                'created_by' => $admin->id,
            ]
        );

        // ==================== الفواتير ====================
        $invoice = Invoice::firstOrCreate(
            [
                'subscription_id' => $subscription1->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ],
            [
                'meter_reading_id' => $reading->id,
                'amount' => 10,
                'discount_amount' => 0,
                'discount_id' => null,
                'final_amount' => 10,
                'currency' => 'ILS',
                'exchange_rate' => null,
                'final_amount_ils' => 10,
                'status' => InvoiceStatus::Pending,
            ]
        );

        // ==================== طرق الدفع ====================
        PaymentMethod::firstOrCreate(
            [
                'user_id' => $owner1->id,
                'type' => PaymentMethodType::Wallet,
            ],
            [
                'is_default' => true,
                'currency' => 'ILS',
            ]
        );

        PaymentMethod::firstOrCreate(
            [
                'user_id' => $owner1->id,
                'type' => PaymentMethodType::Bank,
            ],
            [
                'is_default' => false,
                'currency' => 'ILS',
                'bank_name' => 'بنك فلسطين',
                'account_name' => 'Owner One',
                'account_number' => '123456789',
            ]
        );

        Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter1001->id,
                'generator_id' => $generatorA->id,
                'schedule' => 'night',
            ],
            [
                'agreed_price_per_kw' => $generatorA->price_per_kw,
                'currency' => $generatorA->currency,
                'requested_capacity_kw' => 15,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(30),
                'status' => 'active',
            ]
        );

        Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter1002->id,
                'generator_id' => $generatorB->id,
                'schedule' => '24h',
            ],
            [
                'agreed_price_per_kw' => $generatorB->price_per_kw,
                'currency' => $generatorB->currency,
                'requested_capacity_kw' => 10,
                'contract_type' => 'commercial',
                'start_date' => now(),
                'status' => 'pending',
            ]
        );

        $subscription4 = Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter2001->id,
                'generator_id' => $generatorA->id,
                'schedule' => 'day',
            ],
            [
                'agreed_price_per_kw' => $generatorA->price_per_kw,
                'currency' => $generatorA->currency,
                'requested_capacity_kw' => 25,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(10),
                'status' => 'active',
            ]
        );

        Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter3001->id,
                'generator_id' => $generatorA->id,
                'schedule' => 'night',
            ],
            [
                'agreed_price_per_kw' => $generatorA->price_per_kw,
                'currency' => $generatorA->currency,
                'requested_capacity_kw' => 20,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(5),
                'status' => 'active',
            ]
        );

        Subscription::firstOrCreate(
            [
                'subscriber_meter_id' => $meter2001->id,
                'generator_id' => $generatorB->id,
                'schedule' => 'custom',
                'service_start_time' => '08:00',
                'service_end_time' => '10:00',
            ],
            [
                'agreed_price_per_kw' => $generatorB->price_per_kw,
                'currency' => $generatorB->currency,
                'requested_capacity_kw' => 30,
                'contract_type' => 'commercial',
                'start_date' => now(),
                'status' => 'pending',
            ]
        );

        // ==================== تعيين خطط لأصحاب المولدات ====================
        $basicPlan = Plan::where('code', 'basic')->first();
        $proPlan = Plan::where('code', 'pro')->first();

        if ($basicPlan && ! $owner1->plan_id) {
            $owner1->update(['plan_id' => $basicPlan->id]);
        }
        if ($proPlan && ! $owner2->plan_id) {
            $owner2->update(['plan_id' => $proPlan->id]);
        }

        // ==================== أعطال تجريبية (تغطية حالات متعدّدة) ====================
        \App\Models\Fault::firstOrCreate(
            ['generator_id' => $generatorA->id, 'title' => 'انقطاع متكرر بمولد الرمال'],
            [
                'reported_by' => $subUser1->id,
                'source' => 'subscriber_report',
                'description' => 'المولد بينطفي كل شوي بدون سبب واضح.',
                'priority' => 'high',
                'reported_at' => now()->subDays(3),
                'status' => 'pending_verification',
            ]
        );

        \App\Models\Fault::firstOrCreate(
            ['generator_id' => $generatorA->id, 'title' => 'صوت غريب من المحرك'],
            [
                'reported_by' => $subUser2->id,
                'source' => 'subscriber_report',
                'description' => 'في صوت طرقعة غير طبيعي وقت التشغيل.',
                'priority' => 'medium',
                'reported_at' => now()->subDays(6),
                'status' => 'verified',
                'verified_by' => $owner1->id,
                'verified_at' => now()->subDays(5),
                'repair_method' => 'internal_technician',
            ]
        );

        \App\Models\Fault::firstOrCreate(
            ['generator_id' => $generatorB->id, 'title' => 'انخفاض في الجهد الكهربائي'],
            [
                'reported_by' => $subUser3->id,
                'source' => 'subscriber_report',
                'description' => 'الجهد أقل من المتفق عليه بشكل ملحوظ.',
                'priority' => 'critical',
                'reported_at' => now()->subDays(15),
                'status' => 'resolved',
                'verified_by' => $owner2->id,
                'verified_at' => now()->subDays(14),
                'repair_method' => 'owner_fixed',
                'resolved_at' => now()->subDays(10),
            ]
        );

        // ==================== شكاوى تجريبية (تغطية حالات متعدّدة) ====================
        \App\Models\Complaint::firstOrCreate(
            ['submitted_by' => $subUser1->id, 'subject' => 'تأخر في الرد على الاستفسارات'],
            [
                'description' => 'راسلت المالك من كم يوم وما في رد لسا.',
                'status' => 'pending',
            ]
        );

        \App\Models\Complaint::firstOrCreate(
            ['submitted_by' => $subUser2->id, 'subject' => 'خطأ بقراءة العداد'],
            [
                'description' => 'القراءة المسجَّلة أعلى بكتير من الاستهلاك الفعلي.',
                'status' => 'in_progress',
            ]
        );

        \App\Models\Complaint::firstOrCreate(
            ['submitted_by' => $subUser3->id, 'subject' => 'مشكلة بموعد الصيانة'],
            [
                'description' => 'تم الاتفاق على موعد صيانة ولم يحضر الفني.',
                'status' => 'resolved',
                'resolved_by' => $owner1->id,
                'resolved_at' => now()->subDays(2),
                'resolution_note' => 'تم تحديد موعد بديل وإنجاز الصيانة.',
            ]
        );

        // ==================== عروض تجريبية ====================
        \App\Models\Offer::firstOrCreate(
            ['owner_id' => $owner1->id, 'title' => 'خصم الشتاء 10%'],
            [
                'description' => 'خصم موسمي لكل المشتركين الحاليين.',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'target_mode' => 'all',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'status' => 'active',
            ]
        );

        \App\Models\Offer::firstOrCreate(
            ['owner_id' => $owner2->id, 'title' => 'عرض انتهى بالفعل'],
            [
                'description' => 'عرض تجريبي منتهي، للتأكد من إخفائه من العروض الفعّالة.',
                'discount_type' => 'fixed',
                'discount_value' => 20,
                'target_mode' => 'all',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(30),
                'status' => 'cancelled',
            ]
        );

        // ==================== تنويع حالات الفواتير/الدفعات (Paid/Overdue) ====================
        $readingPaid = MeterReading::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'reading_date' => now()->subDays(35)->toDateString()],
            ['previous_reading' => 960, 'current_reading' => 1000, 'created_by' => $admin->id]
        );

        $invoicePaid = Invoice::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'due_date' => now()->subDays(28)->toDateString()],
            [
                'meter_reading_id' => $readingPaid->id,
                'amount' => 20, 'discount_amount' => 0, 'discount_id' => null,
                'final_amount' => 20, 'currency' => 'ILS', 'exchange_rate' => null,
                'final_amount_ils' => 20, 'status' => InvoiceStatus::Paid,
            ]
        );

        \App\Models\Payment::firstOrCreate(
            ['invoice_id' => $invoicePaid->id, 'transaction_reference' => 'DEMO-PAY-0001'],
            [
                'source' => 'subscriber', 'amount' => 20, 'currency' => 'ILS',
                'amount_ils' => 20, 'status' => 'paid',
                'processed_by' => $owner1->id, 'paid_at' => now()->subDays(27),
                'reviewed_by' => $owner1->id, 'reviewed_at' => now()->subDays(27),
            ]
        );

        $readingOverdue = MeterReading::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'reading_date' => now()->subDays(20)->toDateString()],
            ['previous_reading' => 1020, 'current_reading' => 1055, 'created_by' => $admin->id]
        );

        Invoice::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'due_date' => now()->subDays(13)->toDateString()],
            [
                'meter_reading_id' => $readingOverdue->id,
                'amount' => 17.5, 'discount_amount' => 0, 'discount_id' => null,
                'final_amount' => 17.5, 'currency' => 'ILS', 'exchange_rate' => null,
                'final_amount_ils' => 17.5, 'status' => InvoiceStatus::Overdue,
            ]
        );

        // ==================== دفعة جزئية (Partial Payment scenario) ====================
        $readingPartial = MeterReading::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'reading_date' => now()->subDays(7)->toDateString()],
            ['previous_reading' => 1055, 'current_reading' => 1095, 'created_by' => $admin->id]
        );

        $invoicePartial = Invoice::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'due_date' => now()->addDays(3)->toDateString()],
            [
                'meter_reading_id' => $readingPartial->id,
                'amount' => 20, 'discount_amount' => 0, 'discount_id' => null,
                'final_amount' => 20, 'currency' => 'ILS', 'exchange_rate' => null,
                'final_amount_ils' => 20, 'status' => InvoiceStatus::PartiallyPaid,
            ]
        );

        \App\Models\Payment::firstOrCreate(
            ['invoice_id' => $invoicePartial->id, 'transaction_reference' => 'DEMO-PAY-0002'],
            [
                'source' => 'subscriber', 'amount' => 12, 'currency' => 'ILS',
                'amount_ils' => 12, 'status' => 'paid',
                'processed_by' => $owner1->id, 'paid_at' => now()->subDays(2),
                'reviewed_by' => $owner1->id, 'reviewed_at' => now()->subDays(2),
            ]
        );

        // ==================== طلبات تحويل عداد تجريبية ====================
        SubscriptionMeterTransferRequest::firstOrCreate(
            ['subscription_id' => $subscription1->id, 'from_subscriber_meter_id' => $meter1001->id, 'to_subscriber_meter_id' => $meter1002->id],
            [
                'requested_by' => $subUser1->id,
                'status' => SubscriptionMeterTransferStatus::Pending,
                'reason' => 'انتقلت لمنزل جديد بنفس الحي.',
            ]
        );

        SubscriptionMeterTransferRequest::firstOrCreate(
            ['subscription_id' => $subscription4->id, 'from_subscriber_meter_id' => $meter2001->id, 'to_subscriber_meter_id' => $meter3001->id],
            [
                'requested_by' => $subUser2->id,
                'status' => SubscriptionMeterTransferStatus::Rejected,
                'reason' => 'طلب تجريبي مرفوض للتأكد من تغطية هذه الحالة.',
                'reviewed_by' => $owner1->id,
                'reviewed_at' => now()->subDay(),
                'rejection_reason' => 'العداد الهدف لا يخص نفس المشترك.',
            ]
        );

        // ==================== طلبات انضمام مالكين تجريبية ====================
        OwnerApplication::firstOrCreate(
            ['email' => 'owner-applicant-pending@example.test'],
            [
                'name' => 'مالك متقدّم للانضمام',
                'phone' => '0599111222',
                'password' => Hash::make($this->demoPassword('owner-applicant-pending@example.test')),
                'notes' => 'أمتلك مولدًا بسعة 40 كيلوواط بحي الزيتون.',
                'generator_name' => 'مولد الزيتون الجديد',
                'generator_price_per_kw' => 0.45,
                'generator_currency' => 'ILS',
                'generator_capacity_kw' => 40,
                'generator_city' => 'غزة',
                'generator_neighborhood_id' => $neighborhoodZaytoun->id,
                'generator_address' => 'شارع الزيتون الرئيسي',
                'status' => 'pending',
            ]
        );

        OwnerApplication::firstOrCreate(
            ['email' => 'owner-applicant-approved@example.test'],
            [
                'name' => 'مالك تمت الموافقة عليه',
                'phone' => '0599333444',
                'password' => Hash::make($this->demoPassword('owner-applicant-approved@example.test')),
                'notes' => 'طلب تجريبي بحالة معتمدة.',
                'generator_name' => 'مولد تجريبي معتمد',
                'generator_price_per_kw' => 0.4,
                'generator_currency' => 'ILS',
                'generator_capacity_kw' => 30,
                'generator_city' => 'غزة',
                'generator_address' => 'شارع تجريبي',
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays(3),
            ]
        );

        // ==================== رسائل تواصل تجريبية ====================
        ContactMessage::firstOrCreate(
            ['email' => 'guest-new@example.test', 'subject' => 'general'],
            [
                'name' => 'زائر مهتم',
                'phone' => '0599555666',
                'message' => 'أريد معرفة المزيد عن خدمة المنصة.',
                'status' => 'new',
            ]
        );

        ContactMessage::firstOrCreate(
            ['email' => 'guest-inprogress@example.test', 'subject' => 'technical'],
            [
                'name' => 'مستخدم لديه مشكلة تقنية',
                'phone' => '0599777888',
                'message' => 'لا أستطيع تسجيل الدخول لحسابي.',
                'status' => 'in_progress',
            ]
        );

        ContactMessage::firstOrCreate(
            ['email' => 'guest-resolved@example.test', 'subject' => 'partnership'],
            [
                'name' => 'شريك محتمل',
                'phone' => '0599999000',
                'message' => 'مهتم بشراكة تجارية مع المنصة.',
                'status' => 'resolved',
                'admin_note' => 'تم التواصل والرد على الاستفسار.',
                'handled_by' => $admin->id,
                'handled_at' => now()->subDays(1),
            ]
        );

        // ==================== المقالات ====================
        Article::firstOrCreate(
            ['slug' => 'ala-yagraf-nzam-tashghyl-almwld-almatal'],
            [
                'title' => 'كيف يعمل نظام إدارة المولدات؟',
                'excerpt' => 'نظرة سريعة على كيفية إدارة اشتراكات الكهرباء بالمولدات بشكل رقمي كامل.',
                'content' => "منصة Ampare بتساعد أصحاب المولدات على إدارة مشتركينهم وفواتيرهم وفنييهم بشكل رقمي كامل، بدل الاعتماد على دفاتر ورقية أو ملفات إكسل متفرقة.\n\nمن خلال المنصة، يقدر صاحب المولد يتابع قراءات العدادات، يصدر الفواتير تلقائيًا، ويتواصل مع مشتركيه وفنييه بسهولة.",
                'title_en' => 'How Does the Generator Management System Work?',
                'excerpt_en' => 'A quick look at how electricity subscriptions are managed digitally, end to end.',
                'content_en' => "Ampare helps generator owners manage their subscribers, invoices, and technicians fully digitally, instead of relying on paper logbooks or scattered spreadsheets.\n\nThrough the platform, an owner can track meter readings, generate invoices automatically, and communicate with subscribers and technicians easily.",
                'cover_image_url' => null,
                'author_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ]
        );

        Article::firstOrCreate(
            ['slug' => 'nsayh-latwfyr-alwqwd-lasshab-almwldat'],
            [
                'title' => 'نصائح لتوفير الوقود لأصحاب المولدات',
                'excerpt' => 'خطوات عملية بسيطة تساعد بتقليل استهلاك الوقود وزيادة كفاءة التشغيل.',
                'content' => 'استهلاك الوقود من أكبر التحديات اللي بيواجهها أصحاب المولدات. بهذا المقال، رح نستعرض مجموعة نصائح عملية لتقليل الاستهلاك دون التأثير على جودة الخدمة المقدَّمة للمشتركين.',
                'title_en' => 'Fuel-Saving Tips for Generator Owners',
                'excerpt_en' => 'Simple, practical steps to reduce fuel consumption without affecting service quality.',
                'content_en' => 'Fuel consumption is one of the biggest challenges generator owners face. In this article, we go over a set of practical tips to reduce consumption without affecting the quality of service provided to subscribers.',
                'cover_image_url' => null,
                'author_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]
        );

        Article::firstOrCreate(
            ['slug' => 'mqal-mswda-lm-ynshr-baad'],
            [
                'title' => 'مقال مسودة (لم يُنشر بعد)',
                'excerpt' => 'هذا مقال تجريبي بحالة مسودة، للتأكد من إخفائه بالصفحة العامة.',
                'content' => 'محتوى تجريبي.',
                'cover_image_url' => null,
                'author_id' => $admin->id,
                'is_published' => false,
                'published_at' => null,
            ]
        );

        $this->printSeedCredentials();
    }

    /**
     * SEC-006: كلمة مرور فريدة وقوية عشوائية لكل حساب/سجل تجريبي بدل الاعتماد
     * على نفس القيمة الثابتة 'password' للجميع. تُبنى فقط عند إنشاء السجل
     * فعليًا لأول مرة (firstOrCreate لن يستدعي القيمة إن كان السجل موجودًا
     * مسبقًا، فلا داعي أصلًا لتوليدها بذاك السيناريو، لكن استدعاء الدالة نفسه
     * غير مكلف)، وتُخزَّن مؤقتًا لطباعتها مرة واحدة بنهاية التشغيل فقط.
     */
    private function demoPassword(string $identifier): string
    {
        $password = Str::password(16);
        $this->generatedCredentials[$identifier] = $password;

        return $password;
    }

    /**
     * يطبع كلمات المرور المولَّدة هذا التشغيل فقط، وفقط بالـ console المحلي —
     * أبدًا بملفات الـ log. لا شيء يُطبع لو كل الحسابات كانت موجودة مسبقًا
     * (firstOrCreate لا يستدعي demoPassword حينها، فالمصفوفة تبقى فارغة).
     */
    private function printSeedCredentials(): void
    {
        if (! $this->command || empty($this->generatedCredentials)) {
            return;
        }

        $rows = [];
        foreach ($this->generatedCredentials as $identifier => $password) {
            $rows[] = [$identifier, $password];
        }

        $this->command->info('==================== بيانات دخول الحسابات التجريبية (DatabaseSeeder) ====================');
        $this->command->table(['الحساب', 'كلمة المرور'], $rows);
    }
}
