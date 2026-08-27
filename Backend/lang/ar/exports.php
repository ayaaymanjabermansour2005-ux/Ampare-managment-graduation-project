<?php

/**
 * نصوص تصدير Excel (رؤوس الأعمدة + تسميات الحالات) — عربي.
 * تُستخدَم عبر App\Support\ExportLabel وملفات app/Exports/*.php حتى يتبدّل
 * محتوى ملف الإكسل المُصدَّر فعليًا حسب لغة الطلب (Accept-Language أو ?lang=)
 * بدل ما يكون عربي ثابت دايمًا.
 */
return [

    'headings' => [
        'id' => 'الرقم',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'status' => 'الحالة',
        'created_at' => 'تاريخ الإنشاء',
        'notes' => 'ملاحظات',

        // Subscriptions
        'subscription_id' => 'رقم الاشتراك',
        'subscriber' => 'المشترك',
        'generator' => 'المولد',
        'generator_owner' => 'مالك المولد',
        'price_per_kw' => 'السعر المتفق عليه (لكل كيلوواط)',
        'currency' => 'العملة',
        'start_date' => 'تاريخ البدء',
        'end_date' => 'تاريخ الانتهاء',

        // Payments
        'payment_id' => 'رقم الدفعة',
        'invoice_id' => 'رقم الفاتورة',
        'source' => 'المصدر',
        'amount' => 'المبلغ',
        'amount_ils' => 'المبلغ (شيكل)',
        'exchange_rate' => 'سعر الصرف',
        'payment_method' => 'وسيلة الدفع',
        'paid_at' => 'تاريخ السداد',
        'submitted_at' => 'تاريخ الإرسال',
        'admin_adjustment' => 'تعديل إداري',

        // Subscribers (users)
        'beneficiary_type' => 'نوع الاستفادة',
        'linked_generators_count' => 'عدد المولدات المرتبطة',
        'active_subscriptions' => 'الاشتراكات النشطة',
        'outstanding_balance_ils' => 'الرصيد المستحق (₪)',
        'joined_at' => 'تاريخ الانضمام',

        // Invoices
        'base_amount' => 'المبلغ الأساسي',
        'discount_amount' => 'الخصم',
        'final_amount' => 'المبلغ النهائي',
        'final_amount_ils' => 'المبلغ النهائي (شيكل)',
        'due_date' => 'تاريخ الاستحقاق',
        'issued_at' => 'تاريخ الإصدار',

        // Generators
        'generator_id' => 'رقم المولد',
        'generator_name' => 'اسم المولد',
        'owner' => 'المالك',
        'city' => 'المدينة',
        'capacity_kw' => 'القدرة (kW)',
        'fuel_type' => 'نوع الوقود',
        'fuel_percentage' => 'نسبة الوقود الحالية (%)',
        'active_subscribers' => 'المشتركون النشطون',
        'monthly_revenue_ils' => 'الإيراد الشهري (₪)',
        'added_at' => 'تاريخ الإضافة',

        // Offers
        'offer_id' => 'رقم العرض',
        'title' => 'العنوان',
        'discount' => 'نسبة/قيمة الخصم',
        'target' => 'الفئة المستهدفة',

        // Faults
        'fault_id' => 'رقم العطل',
        'priority' => 'الأولوية',
        'reported_by' => 'مُبلّغ بواسطة',
        'reported_at' => 'تاريخ البلاغ',
        'verified_by' => 'تم التحقق بواسطة',
        'resolved_at' => 'تاريخ الإصلاح',

        // Complaints
        'complaint_id' => 'رقم الشكوى',
        'subject' => 'الموضوع',
        'submitted_by' => 'مقدّم الشكوى',
        'related_to' => 'متعلقة بـ',
        'resolved_by' => 'تم الحل بواسطة',

        // Login logs
        'log_id' => 'الرقم',
        'event' => 'الحدث',
        'user' => 'المستخدم',
        'ip' => 'عنوان الـIP',
        'date' => 'التاريخ',

        // Meter readings
        'reading_id' => 'رقم القراءة',
        'meter_number' => 'رقم العداد',
        'reading_date' => 'تاريخ القراءة',
        'previous_reading' => 'القراءة السابقة',
        'current_reading' => 'القراءة الحالية',
        'consumed_kw' => 'الاستهلاك (كيلوواط)',
        'created_by' => 'أُنشئت بواسطة',

        // Users
        'role' => 'الصلاحية',

        // Technicians
        'technician_id' => 'رقم الفني',
        'assigned_tasks_count' => 'عدد المهام المسندة',
    ],

    'values' => [
        'admin_adjustment' => 'تعديل إداري',
        'unknown_user' => 'غير معروف',
        'event_login_succeeded' => 'تسجيل دخول ناجح',
        'event_login_failed' => 'محاولة دخول فاشلة',
    ],

    // نفس مفاتيح Complaint::COMPLAINABLE_TYPES القصيرة (Generator/Fault/...)
    'related_to' => [
        'Generator' => 'مولد',
        'Fault' => 'عطل',
        'Subscription' => 'اشتراك',
        'Invoice' => 'فاتورة',
        'Payment' => 'دفعة',
    ],

    // exports.enum.<EnumBasename>.<case value>
    'enum' => [
        'SubscriptionStatus' => [
            'pending' => 'قيد المراجعة',
            'active' => 'نشط',
            'suspended' => 'موقوف',
            'cancelled' => 'ملغى',
            'rejected' => 'مرفوض',
        ],
        'OperatingSchedule' => [
            'day' => 'فترة نهارية',
            'night' => 'فترة ليلية',
            '24h' => '24 ساعة',
            'custom' => 'فترة مخصصة',
        ],
        'InvoiceStatus' => [
            'pending' => 'قيد الانتظار',
            'partially_paid' => 'مسدّدة جزئيًا',
            'paid' => 'مسدّدة بالكامل',
            'overdue' => 'متأخرة',
            'cancelled' => 'ملغاة',
        ],
        'MeterReadingStatus' => [
            'pending_approval' => 'بانتظار الاعتماد',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
        ],
        'PaymentStatus' => [
            'pending' => 'بانتظار المراجعة',
            'needs_correction' => 'يحتاج تعديل',
            'paid' => 'تم الدفع',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
        ],
        'OfferStatus' => [
            'active' => 'فعّال',
            'cancelled' => 'ملغى',
        ],
        'FaultStatus' => [
            'pending_verification' => 'بانتظار التحقق',
            'verified' => 'تم التحقق منه',
            'rejected' => 'بلاغ غير صحيح',
            'in_repair' => 'قيد الإصلاح',
            'resolved' => 'تم الإصلاح',
            'closed' => 'مغلق',
        ],
        'ComplaintStatus' => [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
        ],
        'UserStatus' => [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'موقوف',
            'pending_review' => 'بانتظار مراجعة الإدارة',
        ],
        'GeneratorStatus' => [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'maintenance' => 'تحت الصيانة',
            'pending_verification' => 'بانتظار اعتماد الأدمن',
            'rejected' => 'مرفوض',
        ],
        'BeneficiaryType' => [
            'normal' => 'عادي',
            'special' => 'مستفيد من تبرّع',
        ],
        'PaymentSource' => [
            'subscriber' => 'دفعة من المشترك',
            'adjustment' => 'تسوية إدارية',
            'gateway' => 'بوابة دفع إلكتروني',
        ],
        'PaymentMethodType' => [
            'bank' => 'تحويل بنكي',
            'wallet' => 'محفظة إلكترونية',
            'cash' => 'نقدًا',
        ],
        'FuelType' => [
            'diesel' => 'ديزل',
            'gas' => 'غاز',
            'petrol' => 'بنزين',
            'dual' => 'مزدوج (ديزل/غاز)',
        ],
        'OfferTargetMode' => [
            'all' => 'جميع المشتركين',
            'beneficiary' => 'حسب فئة المستفيدين',
            'selected' => 'مشتركون محدَّدون يدويًا',
        ],
        'OfferDiscountType' => [
            'percentage' => 'نسبة مئوية',
            'fixed' => 'مبلغ ثابت',
        ],
        'FaultPriority' => [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'critical' => 'حرجة',
        ],
        'Role' => [
            'admin' => 'مدير',
            'generator_owner' => 'مالك مولد',
            'subscriber' => 'مشترك',
            'technician' => 'فني',
        ],
        'TechnicianStatus' => [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'موقوف',
        ],
    ],

];
