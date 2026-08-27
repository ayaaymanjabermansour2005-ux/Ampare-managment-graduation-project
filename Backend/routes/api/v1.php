<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AdminAnnouncementController;
use App\Http\Controllers\Api\AdminBackupController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\ArticleCommentController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommissionTierController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FaultController;
use App\Http\Controllers\Api\FaultPredictionController;
use App\Http\Controllers\Api\FuelController;
use App\Http\Controllers\Api\GeneratorController;
use App\Http\Controllers\Api\GeneratorDiagnosticController;
use App\Http\Controllers\Api\GeneratorScheduleController;
use App\Http\Controllers\Api\GuestDemoLoginController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LiveScheduleController;
use App\Http\Controllers\Api\LoginLogController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MeterReadingController;
use App\Http\Controllers\Api\NeighborhoodController;
use App\Http\Controllers\Api\NeighborhoodDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OwnerApplicationController;
use App\Http\Controllers\Api\OwnerMonthlyReportController;
use App\Http\Controllers\Api\OwnerRatingController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlatformCommissionController;
use App\Http\Controllers\Api\PublicAnalyticsController;
use App\Http\Controllers\Api\PublicGeneratorsListController;
use App\Http\Controllers\Api\PublicGeneratorsMapController;
use App\Http\Controllers\Api\PublicPlatformStatsController;
use App\Http\Controllers\Api\RoleDashboardController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SidebarController;
use App\Http\Controllers\Api\SubscriberController;
use App\Http\Controllers\Api\SubscriberMeterController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionMeterTransferRequestController;
use App\Http\Controllers\Api\SubscriptionServiceRequestController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\TechnicianPaymentController;
use App\Http\Controllers\Api\TechnicianTaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserLockoutController;
use App\Http\Controllers\Api\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:3,1');

    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('verification.verify');

    Route::post('email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:email-verification-resend');

    Route::get('neighborhoods', [NeighborhoodController::class, 'index']);

    /*
    |----------------------------------------------------------------------
    | Owner Applications — طلب انضمام صاحب مولد (عام، بدون تسجيل دخول)
    |----------------------------------------------------------------------
    */
    Route::post('owner-applications', [OwnerApplicationController::class, 'store'])
        ->middleware('throttle:3,1');

    /*
    |----------------------------------------------------------------------
    | Auth Session Management (Protected)
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'active', 'maintenance'])->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('me', [AuthController::class, 'me']);

        Route::patch('password', [AuthController::class, 'changePassword']);

        Route::post('logout-other-devices', [AuthController::class, 'logoutOtherDevices']);

        Route::get('sessions', [AuthController::class, 'sessions']);
        Route::delete('sessions/{sessionId}', [AuthController::class, 'revokeSession']);
        Route::get('login-log', [AuthController::class, 'loginLog']);

        Route::delete('account', [AuthController::class, 'deleteAccount']);
    });
});

/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active', 'maintenance', 'prevent-guest-mutations'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Admin — Dashboard, Roles & Permissions, Settings, Plans, Articles,
    | Login Logs —
    |----------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {
        Route::get('admin/login-logs', [LoginLogController::class, 'index']);
        Route::get('admin/login-logs/export', [LoginLogController::class, 'export']);
        Route::get('admin/sidebar/badge-counts', [SidebarController::class, 'badgeCounts']);

        Route::get('admin/contact-messages', [ContactMessageController::class, 'index']);
        Route::get('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'show']);
        Route::patch('admin/contact-messages/{contactMessage}/status', [ContactMessageController::class, 'updateStatus']);

        Route::get('admin/dashboard/stats', [AdminDashboardController::class, 'stats']);
        Route::get('admin/dashboard/payments-financial-summary', [AdminDashboardController::class, 'paymentsFinancialSummary']);
        Route::get('admin/dashboard/invoice-status-breakdown', [AdminDashboardController::class, 'invoiceStatusBreakdown']);
        Route::get('admin/dashboard/alerts', [AdminDashboardController::class, 'alerts']);
        Route::get('admin/dashboard/charts/revenue', [AdminDashboardController::class, 'revenueChart']);
        Route::get('admin/dashboard/charts/subscriber-growth', [AdminDashboardController::class, 'subscriberGrowthChart']);
        Route::get('admin/dashboard/charts/fuel', [AdminDashboardController::class, 'fuelChart']);
        Route::get('admin/dashboard/charts/maintenance', [AdminDashboardController::class, 'maintenanceChart']);

        Route::get('admin/dashboard/generators-map', [AdminDashboardController::class, 'generatorsMap']);
        Route::post('admin/announcements', [AdminAnnouncementController::class, 'store']);
        Route::post('admin/backup/run', [AdminBackupController::class, 'run']);

        Route::get('admin/roles-permissions', [RolePermissionController::class, 'index']);
        Route::patch('admin/roles/{role}/permissions', [RolePermissionController::class, 'sync']);
        Route::get('admin/settings', [SettingController::class, 'index']);
        Route::patch('admin/settings', [SettingController::class, 'update']);

        Route::post('admin/neighborhoods', [NeighborhoodController::class, 'store']);
        Route::patch('admin/neighborhoods/{neighborhood}', [NeighborhoodController::class, 'update']);
        Route::delete('admin/neighborhoods/{neighborhood}', [NeighborhoodController::class, 'destroy']);
        Route::get('admin/articles', [ArticleController::class, 'index']);
        Route::post('admin/articles', [ArticleController::class, 'store']);
        Route::patch('admin/articles/{article}', [ArticleController::class, 'update']);
        Route::delete('admin/articles/{article}', [ArticleController::class, 'destroy']);

        Route::post('admin/articles/{article}/attachments', [ArticleController::class, 'storeAttachment']);

        /*
        |------------------------------------------------------------------
        | مراجعة تعليقات المقالات (الأدمن)
        |------------------------------------------------------------------
        */
        Route::get('admin/article-comments', [ArticleCommentController::class, 'adminIndex']);
        Route::post('admin/article-comments/{comment}/approve', [ArticleCommentController::class, 'approve']);
        Route::post('admin/article-comments/{comment}/reject', [ArticleCommentController::class, 'reject']);
        Route::post('admin/article-comments/{comment}/reply', [ArticleCommentController::class, 'reply']);
        Route::delete('admin/article-comments/{comment}/reply', [ArticleCommentController::class, 'deleteReply']);
        Route::delete('admin/article-comments/{comment}', [ArticleCommentController::class, 'destroy']);
    });

    Route::get('preferences', [UserPreferenceController::class, 'index']);
    Route::patch('preferences', [UserPreferenceController::class, 'update']);

    Route::get('owner/dashboard/stats', [RoleDashboardController::class, 'ownerStats']);
    Route::get('subscriber/dashboard/stats', [RoleDashboardController::class, 'subscriberStats']);
    Route::get('technician/dashboard/stats', [RoleDashboardController::class, 'technicianStats']);
    Route::get('plans', [PlanController::class, 'index']);
    Route::patch('users/{user}/plan', [PlanController::class, 'assign']);

    /*
    |----------------------------------------------------------------------
    | Technicians
    |----------------------------------------------------------------------
    */

    Route::get('technicians', [TechnicianController::class, 'index'])
        ->middleware('permission:technicians.view');

    // export لازم تُسجَّل قبل /{technician} (نفس السبب في faults/export).
    Route::get('technicians/export', [TechnicianController::class, 'export'])
        ->middleware('permission:technicians.view');

    Route::get('technicians/{technician}', [TechnicianController::class, 'show'])
        ->middleware('permission:technicians.view');

    Route::post('technicians', [TechnicianController::class, 'store'])
        ->middleware('permission:technicians.create');

    Route::post('technicians/create-account', [TechnicianController::class, 'createAccount'])
        ->middleware('permission:technicians.create');

    Route::post('technicians/{technician}/generators/{generator}', [TechnicianController::class, 'linkGenerator'])
        ->middleware('permission:technicians.update');

    Route::delete('technicians/{technician}/generators/{generator}', [TechnicianController::class, 'unlinkGenerator'])
        ->middleware('permission:technicians.update');

    Route::patch('technicians/{technician}', [TechnicianController::class, 'update'])
        ->middleware('permission:technicians.update');

    Route::delete('technicians/{technician}', [TechnicianController::class, 'destroy'])
        ->middleware('permission:technicians.delete');

    Route::get('technicians/{technician}/attachments', [TechnicianController::class, 'attachments'])
        ->middleware('permission:technicians.view');

    Route::post('technicians/{technician}/attachments', [TechnicianController::class, 'storeAttachment'])
        ->middleware('permission:technicians.update');

    Route::get('generators/{generator}/available-technicians', [TechnicianController::class, 'availableForGenerator'])
        ->middleware('permission:technicians.view');

    /*
    |----------------------------------------------------------------------
    | Technician Tasks
    |----------------------------------------------------------------------
    */

    Route::get('technician-tasks', [TechnicianTaskController::class, 'index'])
        ->middleware('permission:technician-tasks.view');

    Route::get('technician-tasks/stats', [TechnicianTaskController::class, 'stats'])
        ->middleware('permission:technician-tasks.view');

    Route::get('technician-tasks/{technician_task}', [TechnicianTaskController::class, 'show'])
        ->middleware('permission:technician-tasks.view');

    Route::post('technician-tasks', [TechnicianTaskController::class, 'store'])
        ->middleware('permission:technician-tasks.create');

    Route::patch('technician-tasks/{technician_task}/assign', [TechnicianTaskController::class, 'assign'])
        ->middleware('permission:technician-tasks.assign');

    Route::patch('technician-tasks/{technician_task}/on-the-way', [TechnicianTaskController::class, 'onTheWay'])
        ->middleware('permission:technician-tasks.start');

    Route::patch('technician-tasks/{technician_task}/start', [TechnicianTaskController::class, 'start'])
        ->middleware('permission:technician-tasks.start');

    Route::patch('technician-tasks/{technician_task}/waiting-parts', [TechnicianTaskController::class, 'waitingParts'])
        ->middleware('permission:technician-tasks.start');

    Route::patch('technician-tasks/{technician_task}/submit', [TechnicianTaskController::class, 'submit'])
        ->middleware('permission:technician-tasks.submit');

    Route::patch('technician-tasks/{technician_task}/review', [TechnicianTaskController::class, 'review'])
        ->middleware('permission:technician-tasks.review');

    Route::patch('technician-tasks/{technician_task}/cancel', [TechnicianTaskController::class, 'cancel'])
        ->middleware('permission:technician-tasks.cancel');

    Route::post('technician-tasks/{technician_task}/rate', [TechnicianTaskController::class, 'rate'])
        ->middleware('permission:technician-ratings.create');

    /*
    |----------------------------------------------------------------------
    | Generators
    |----------------------------------------------------------------------
    */

    Route::get('generators/available', [GeneratorController::class, 'available'])
        ->middleware('permission:generators.view');

    Route::get('generators/export', [GeneratorController::class, 'export'])
        ->middleware('permission:generators.view');

    Route::get('generators/cities', [GeneratorController::class, 'cities'])
        ->middleware('permission:generators.view');

    Route::get('generators/stats', [GeneratorController::class, 'stats'])
        ->middleware('permission:generators.view');

    Route::get('generators', [GeneratorController::class, 'index'])
        ->middleware('permission:generators.view');

    Route::post('generators', [GeneratorController::class, 'store'])
        ->middleware('permission:generators.create');

    Route::get('/generators/map-points', [GeneratorController::class, 'mapPoints']);
    Route::get('generators/{generator}', [GeneratorController::class, 'show'])
        ->middleware('permission:generators.view');

    Route::patch('generators/{generator}', [GeneratorController::class, 'update'])
        ->middleware('permission:generators.update');

    Route::patch('generators/{generator}/transfer-owner', [GeneratorController::class, 'transferOwnership'])
        ->middleware('permission:generators.update');

    Route::delete('generators/{generator}', [GeneratorController::class, 'destroy'])
        ->middleware('permission:generators.delete');

    Route::get('generators/{generator}/qr', [GeneratorController::class, 'qrCode'])
        ->middleware('permission:generators.view');

    Route::get('generators/{generator}/attachments', [GeneratorController::class, 'attachments'])
        ->middleware('permission:generators.view');

    Route::get('generators/{generator}/technicians', [GeneratorController::class, 'technicians'])
        ->middleware('permission:generators.view');

    Route::post('generators/{generator}/attachments', [GeneratorController::class, 'storeAttachment'])
        ->middleware('permission:generators.update');

    Route::get('generators/{generator}/health-reports', [GeneratorController::class, 'healthReports'])
        ->middleware('permission:generators.view');
    Route::get('generators/{generator}/timeline', [GeneratorController::class, 'timeline']);

    Route::get('generators/{generator}/quick-scan', [GeneratorController::class, 'quickScan']);

    Route::patch('generators/{generator}/verify', [GeneratorController::class, 'verify'])
        ->middleware('permission:generators.update');

    Route::patch('generators/{generator}/reject', [GeneratorController::class, 'reject'])
        ->middleware('permission:generators.update');
    /*
    |----------------------------------------------------------------------
    | Generator Fuel
    |----------------------------------------------------------------------
    */

    Route::get('generators/{generator}/fuel/purchases', [FuelController::class, 'purchases'])
        ->middleware('permission:generators.view');

    Route::post('generators/{generator}/fuel/purchases', [FuelController::class, 'storePurchase'])
        ->middleware('permission:generators.record');

    Route::get('generators/{generator}/fuel/readings', [FuelController::class, 'readings'])
        ->middleware('permission:generators.view');

    Route::post('generators/{generator}/fuel/readings', [FuelController::class, 'storeReading'])
        ->middleware('permission:generators.update');

    Route::get('generators/{generator}/fuel/consumption', [FuelController::class, 'consumption'])
        ->middleware('permission:generators.view');

    Route::get('generators/{generator}/fuel/cost-per-kwh', [FuelController::class, 'costPerKwh'])
        ->middleware('permission:generators.view');

    Route::get('generators/{generator}/fuel/status', [FuelController::class, 'status'])
        ->middleware('permission:generators.view');

    /*
    |----------------------------------------------------------------------
    | Generator Schedules — جدول تشغيل المولد المُعلَن (Rationing Board)
    |----------------------------------------------------------------------
    */

    Route::get('generators/{generator}/schedules', [GeneratorScheduleController::class, 'index'])
        ->middleware('permission:generator-schedules.view');

    Route::post('generators/{generator}/schedules', [GeneratorScheduleController::class, 'store'])
        ->middleware('permission:generator-schedules.create');

    Route::patch('generator-schedules/{generator_schedule}', [GeneratorScheduleController::class, 'update'])
        ->middleware('permission:generator-schedules.update');

    Route::delete('generator-schedules/{generator_schedule}', [GeneratorScheduleController::class, 'destroy'])
        ->middleware('permission:generator-schedules.delete');
    /*
    |----------------------------------------------------------------------
    | Generator Diagnostics — قراءات المحرك + التحليل بالذكاء الاصطناعي
    |----------------------------------------------------------------------
    */

    Route::get('generators/{generator}/diagnostics', [GeneratorDiagnosticController::class, 'index'])
        ->middleware('permission:generators.view');

    Route::post('generators/{generator}/diagnostics', [GeneratorDiagnosticController::class, 'store'])
        ->middleware('permission:generators.record');

    Route::post('generator-diagnostics/{reading}/analyze', [GeneratorDiagnosticController::class, 'analyze'])
        ->middleware(['permission:generators.record', 'throttle:ai-diagnostics']);

    /*
    |----------------------------------------------------------------------
    | AI Chat — المساعد الذكي لتحليل واستشارة أعطال المولدات
    |----------------------------------------------------------------------
    */

    Route::get('ai-chat/available-generators', [AiChatController::class, 'availableGenerators'])
        ->middleware('permission:ai-chat.use');

    Route::get('ai-chat/sessions', [AiChatController::class, 'index'])
        ->middleware('permission:ai-chat.use');

    Route::get('ai-chat/sessions/{aiChatSession}', [AiChatController::class, 'show'])
        ->middleware('permission:ai-chat.use');

    Route::post('ai-chat/sessions', [AiChatController::class, 'store'])
        ->middleware(['permission:ai-chat.use', 'throttle:ai-chat', 'idempotency']);

    Route::post('ai-chat/sessions/{aiChatSession}/messages', [AiChatController::class, 'sendMessage'])
        ->middleware(['permission:ai-chat.use', 'throttle:ai-chat', 'idempotency']);

    Route::post('ai-chat/sessions/{aiChatSession}/submit-as-prediction', [AiChatController::class, 'submitAsPrediction'])
        ->middleware('permission:ai-chat.use');

    Route::post('ai-chat/sessions/{aiChatSession}/submit-as-fault-report', [AiChatController::class, 'submitAsFaultReport'])
        ->middleware('permission:ai-chat.use');

    /*
    |----------------------------------------------------------------------
    | Subscriber Meters
    |----------------------------------------------------------------------
    */

    Route::get('subscriber-meters', [SubscriberMeterController::class, 'index'])
        ->middleware('permission:subscriber-meters.view');

    Route::get('subscriber-meters/{subscriber_meter}', [SubscriberMeterController::class, 'show'])
        ->middleware('permission:subscriber-meters.view');

    Route::post('subscriber-meters', [SubscriberMeterController::class, 'store'])
        ->middleware('permission:subscriber-meters.create');

    Route::patch('subscriber-meters/{subscriber_meter}', [SubscriberMeterController::class, 'update'])
        ->middleware('permission:subscriber-meters.update');

    Route::delete('subscriber-meters/{subscriber_meter}', [SubscriberMeterController::class, 'destroy'])
        ->middleware('permission:subscriber-meters.delete');

    Route::get('subscriber-meters/{subscriber_meter}/qr', [SubscriberMeterController::class, 'qrCode'])
        ->middleware('permission:subscriber-meters.view');

    /*
    |----------------------------------------------------------------------
    | Subscribers
    |----------------------------------------------------------------------
    */

    Route::patch('subscribers/{subscriber}/beneficiary-type', [SubscriberController::class, 'updateBeneficiaryType'])
        ->middleware('permission:subscribers.updateBeneficiaryType');

    /*
    |----------------------------------------------------------------------
    | Subscriptions
    |----------------------------------------------------------------------
    */

    Route::get('subscriptions', [SubscriptionController::class, 'index'])
        ->middleware('permission:subscriptions.view');

    Route::get('subscriptions/export', [SubscriptionController::class, 'export'])
        ->middleware('permission:subscriptions.view');

    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
        ->middleware('permission:subscriptions.view');

    Route::post('subscriptions', [SubscriptionController::class, 'store'])
        ->middleware('permission:subscriptions.create');

    Route::patch('subscriptions/{subscription}/status', [SubscriptionController::class, 'updateStatus'])
        ->middleware('permission:subscriptions.updateStatus');

    Route::get('subscriptions/{subscription}/contract-pdf', [SubscriptionController::class, 'downloadContractPdf'])
        ->middleware('permission:subscriptions.view');
    Route::patch('subscriptions/{subscription}/transfer', [SubscriptionController::class, 'transfer'])
        ->middleware('permission:subscriptions.transfer');

    /*
    |----------------------------------------------------------------------
    | Subscriptions — Owner-scoped (إضافة/نقل اشتراك من طرف مالك المولد
    | نفسه — مسارات منفصلة عن store()/transfer() الإداريين، بنفس صلاحيات
    | subscriptions.create / subscriptions.transfer لكن مقيَّدة بملكية
    | المولد عبر SubscriptionPolicy::createByOwner / transferByOwn)
    |----------------------------------------------------------------------
    */

    Route::post('owner/subscriptions', [SubscriptionController::class, 'storeByOwner'])
        ->middleware(['role:generator_owner', 'permission:subscriptions.create']);

    Route::patch('owner/subscriptions/{subscription}/transfer', [SubscriptionController::class, 'transferByOwner'])
        ->middleware(['role:generator_owner', 'permission:subscriptions.transfer']);

    /*
    |----------------------------------------------------------------------
    | Subscription Meter Transfer Requests — طلب المشترك نقل اشتراكه من
    | عداد إلى عداد آخر يخصه (كلاهما فعّال)؛ يراجعها مالك المولد (أو الأدمن)
    | بالموافقة/الرفض. الموافقة تُنفّذ النقل الفعلي على subscriber_meter_id
    | للاشتراك ضمن معاملة واحدة — راجع ApproveSubscriptionMeterTransferRequestAction.
    |----------------------------------------------------------------------
    */

    Route::get('subscription-meter-transfers', [SubscriptionMeterTransferRequestController::class, 'index'])
        ->middleware('permission:subscription-meter-transfers.view');

    Route::post('subscription-meter-transfers', [SubscriptionMeterTransferRequestController::class, 'store'])
        ->middleware('permission:subscription-meter-transfers.create');

    Route::patch('subscription-meter-transfers/{subscription_meter_transfer}/approve', [SubscriptionMeterTransferRequestController::class, 'approve'])
        ->middleware('permission:subscription-meter-transfers.approve');

    Route::patch('subscription-meter-transfers/{subscription_meter_transfer}/reject', [SubscriptionMeterTransferRequestController::class, 'reject'])
        ->middleware('permission:subscription-meter-transfers.reject');

    /*
    |----------------------------------------------------------------------
    | Owner Ratings — تقييم المشترك لصاحب المولد
    |----------------------------------------------------------------------
    */

    Route::post('subscriptions/{subscription}/owner-rating', [OwnerRatingController::class, 'store'])
        ->middleware('permission:owner-ratings.create');
    Route::get('owners/{owner}/ratings', [OwnerRatingController::class, 'index'])
        ->middleware('permission:owner-ratings.view');

    /*
    |----------------------------------------------------------------------
    | Subscription Service Requests
    |----------------------------------------------------------------------
    */

    Route::get('subscription-service-requests', [SubscriptionServiceRequestController::class, 'index'])
        ->middleware('permission:service-requests.view');

    Route::get('subscription-service-requests/{serviceRequest}', [SubscriptionServiceRequestController::class, 'show'])
        ->middleware('permission:service-requests.view');

    Route::post('subscription-service-requests', [SubscriptionServiceRequestController::class, 'store'])
        ->middleware('permission:service-requests.create');

    Route::patch('subscription-service-requests/{serviceRequest}/review', [SubscriptionServiceRequestController::class, 'review'])
        ->middleware('permission:service-requests.review');

    Route::patch('subscription-service-requests/{serviceRequest}/cancel', [SubscriptionServiceRequestController::class, 'cancel'])
        ->middleware('permission:service-requests.cancel');

    /*
    |----------------------------------------------------------------------
    | Meter Readings
    |----------------------------------------------------------------------
    */

    Route::get('meter-readings', [MeterReadingController::class, 'index'])
        ->middleware('permission:meter-readings.view');

    Route::get('meter-readings/overdue-subscribers', [MeterReadingController::class, 'overdueSubscribers'])
        ->middleware('permission:meter-readings.view');

    Route::get('meter-readings/subscriptions/{subscription}/history', [MeterReadingController::class, 'history'])
        ->middleware('permission:meter-readings.view');

    // export لازم تُسجَّل قبل /{meter_reading} (نفس السبب في faults/export).
    Route::get('meter-readings/export', [MeterReadingController::class, 'export'])
        ->middleware('permission:meter-readings.view');

    Route::get('meter-readings/{meter_reading}', [MeterReadingController::class, 'show'])
        ->middleware('permission:meter-readings.view');

    Route::post('meter-readings', [MeterReadingController::class, 'store'])
        ->middleware(['permission:meter-readings.create', 'idempotency'])
        ->name('meter-readings.store');

    Route::patch('meter-readings/{meter_reading}', [MeterReadingController::class, 'update'])
        ->middleware('permission:meter-readings.update');

    Route::delete('meter-readings/{meter_reading}', [MeterReadingController::class, 'destroy'])
        ->middleware('permission:meter-readings.delete');

    Route::patch('meter-readings/{meter_reading}/approve', [MeterReadingController::class, 'approve'])
        ->middleware('permission:meter-readings.approve');

    Route::patch('meter-readings/{meter_reading}/reject', [MeterReadingController::class, 'reject'])
        ->middleware('permission:meter-readings.approve');

    Route::post('meter-readings/{meter_reading}/attachments', [MeterReadingController::class, 'storeAttachment'])
        ->middleware('permission:meter-readings.create');

    /*
    |----------------------------------------------------------------------
    | Invoices
    |----------------------------------------------------------------------
    */

    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('permission:invoices.view');

    Route::get('invoices/export', [InvoiceController::class, 'export'])
        ->middleware('permission:invoices.view');

    Route::get('invoices/{invoice}/payment-methods', [InvoiceController::class, 'paymentMethods'])
        ->middleware('permission:invoices.view');

    Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
        ->middleware('permission:invoices.cancel');

    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->middleware('permission:invoices.view');

    Route::get('invoices/{invoice}/qr', [InvoiceController::class, 'qrCode'])
        ->middleware('permission:invoices.view');

    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('permission:invoices.view');

    Route::patch('invoices/{invoice}/correct', [InvoiceController::class, 'correct'])
        ->middleware(['permission:invoices.correct', 'idempotency']);

    Route::post('invoices/{invoice}/reissue', [InvoiceController::class, 'reissue'])
        ->middleware(['permission:invoices.reissue', 'idempotency']);

    /*
    |----------------------------------------------------------------------
    | Payments
    |----------------------------------------------------------------------
    */

    Route::get('payments', [PaymentController::class, 'index'])
        ->middleware('permission:payments.view');

    Route::get('payments/export', [PaymentController::class, 'export'])
        ->middleware('permission:payments.view');

    Route::get('payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('permission:payments.view');

    Route::post('payments', [PaymentController::class, 'store'])
        ->middleware(['permission:payments.create', 'throttle:payments', 'idempotency'])
        ->name('payments.store');

    Route::post('payments/gateway', [PaymentController::class, 'storeViaGateway'])
        ->middleware(['permission:payments.create', 'throttle:payments', 'idempotency'])
        ->name('payments.gateway');

    Route::patch('payments/{payment}/approve', [PaymentController::class, 'approve'])
        ->middleware('permission:payments.approve');

    Route::patch('payments/{payment}/reject', [PaymentController::class, 'reject'])
        ->middleware('permission:payments.reject');

    Route::patch('payments/{payment}/needs-correction', [PaymentController::class, 'needsCorrection'])
        ->middleware('permission:payments.approve');

    Route::patch('payments/{payment}/resubmit', [PaymentController::class, 'resubmit'])
        ->middleware('permission:payments.create');

    Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])
        ->middleware('permission:payments.create');

    /*
    |----------------------------------------------------------------------
    | Technician Payments (Owner <-> Technician, excluded from platform commission)
    |----------------------------------------------------------------------
    */

    Route::get('technician-payments', [TechnicianPaymentController::class, 'index'])
        ->middleware('permission:technician-payments.view');

    Route::get('technician-payments/{technician_payment}', [TechnicianPaymentController::class, 'show'])
        ->middleware('permission:technician-payments.view');

    Route::post('technician-payments', [TechnicianPaymentController::class, 'store'])
        ->middleware(['permission:technician-payments.create', 'idempotency']);

    Route::patch('technician-payments/{technician_payment}/approve', [TechnicianPaymentController::class, 'approve'])
        ->middleware('permission:technician-payments.approve');

    Route::patch('technician-payments/{technician_payment}/reject', [TechnicianPaymentController::class, 'reject'])
        ->middleware('permission:technician-payments.reject');

    Route::get('technician-payments/{technician_payment}/attachments', [TechnicianPaymentController::class, 'attachments'])
        ->middleware('permission:technician-payments.view');

    // Coarse gate only (any role that can see technician payments); the real,
    // per-record authorization is TechnicianPaymentPolicy::manageAttachments —
    // same defense-in-depth pattern used for approve/reject on this resource.
    Route::post('technician-payments/{technician_payment}/attachments', [TechnicianPaymentController::class, 'storeAttachment'])
        ->middleware('permission:technician-payments.view');

    /*
    |----------------------------------------------------------------------
    | Attachments
    |----------------------------------------------------------------------
    */

    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])
        ->middleware('permission:attachments.view')
        ->name('attachments.show');

    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('permission:attachments.view')
        ->name('attachments.download');

    Route::get('attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->middleware('permission:attachments.view')
        ->name('attachments.preview');

    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->middleware('permission:attachments.delete')
        ->name('attachments.destroy');

    /*
    |----------------------------------------------------------------------
    | Payment Methods
    |----------------------------------------------------------------------
    */

    Route::get('payment-methods', [PaymentMethodController::class, 'index'])
        ->middleware('permission:payment-methods.view');

    Route::post('payment-methods', [PaymentMethodController::class, 'store'])
        ->middleware('permission:payment-methods.create');

    Route::put('payment-methods/{payment_method}', [PaymentMethodController::class, 'update'])
        ->middleware('permission:payment-methods.update');

    Route::delete('payment-methods/{payment_method}', [PaymentMethodController::class, 'destroy'])
        ->middleware('permission:payment-methods.delete');

    /*
    |----------------------------------------------------------------------
    | Platform Commissions
    |----------------------------------------------------------------------
    */

    Route::get('platform-commissions', [PlatformCommissionController::class, 'index'])
        ->middleware('permission:platform-commissions.view');

    Route::get('platform-commissions/summary', [PlatformCommissionController::class, 'summary'])
        ->middleware('permission:platform-commissions.view');

    Route::get('platform-commissions/report-pdf', [PlatformCommissionController::class, 'downloadReportPdf'])
        ->middleware('permission:platform-commissions.view');

    Route::get('platform-commissions/export', [PlatformCommissionController::class, 'export'])
        ->middleware('permission:platform-commissions.view');

    Route::patch('platform-commissions/{platform_commission}/status', [PlatformCommissionController::class, 'updateStatus'])
        ->middleware('permission:platform-commissions.updateStatus');

    /*
    |----------------------------------------------------------------------
    | Commission Settings & Tiers — إعدادات عمولة كل مالك + الشرائح
    | التلقائية العامة (أدمن فقط)
    |----------------------------------------------------------------------
    */

    Route::patch('users/{user}/commission-settings', [UserController::class, 'updateCommissionSettings'])
        ->middleware('permission:platform-commissions.manage-settings');

    Route::get('commission-tiers', [CommissionTierController::class, 'index'])
        ->middleware('permission:commission-tiers.manage');

    Route::post('commission-tiers', [CommissionTierController::class, 'store'])
        ->middleware('permission:commission-tiers.manage');

    Route::patch('commission-tiers/{commission_tier}', [CommissionTierController::class, 'update'])
        ->middleware('permission:commission-tiers.manage');

    Route::delete('commission-tiers/{commission_tier}', [CommissionTierController::class, 'destroy'])
        ->middleware('permission:commission-tiers.manage');

    /*
    |----------------------------------------------------------------------
    | Owner Monthly Report
    |----------------------------------------------------------------------
    */

    Route::get('owner-monthly-report/download', [OwnerMonthlyReportController::class, 'downloadPdf']);

    /*
    |----------------------------------------------------------------------
    | Faults
    |----------------------------------------------------------------------
    */

    Route::get('faults', [FaultController::class, 'index'])
        ->middleware('permission:faults.view');

    // export لازم تُسجَّل قبل faults/{fault} (نفس ملاحظة owner-applications/export
    // بالأسفل) لأن لاراڤل غير هيك حيفسّر "export" كمعرّف عطل (Route Model Binding).
    Route::get('faults/export', [FaultController::class, 'export'])
        ->middleware('permission:faults.view');

    Route::post('faults', [FaultController::class, 'store'])
        ->middleware('permission:faults.create');

    Route::get('faults/{fault}', [FaultController::class, 'show'])
        ->middleware('permission:faults.view');

    Route::patch('faults/{fault}/verify', [FaultController::class, 'verify'])
        ->middleware('permission:faults.updateStatus');

    Route::patch('faults/{fault}/decide-repair', [FaultController::class, 'decideRepair'])
        ->middleware('permission:faults.updateStatus');

    Route::patch('faults/{fault}/override-status', [FaultController::class, 'overrideStatus'])
        ->middleware('permission:faults.override_status');

    Route::delete('faults/{fault}', [FaultController::class, 'destroy'])
        ->middleware('permission:faults.delete');

    /*
    |----------------------------------------------------------------------
    | Fault Predictions
    |----------------------------------------------------------------------
    */

    Route::get('fault-predictions', [FaultPredictionController::class, 'index'])
        ->middleware('permission:fault-predictions.view');

    Route::get('fault-predictions/{fault_prediction}', [FaultPredictionController::class, 'show'])
        ->middleware('permission:fault-predictions.view');

    Route::post('fault-predictions', [FaultPredictionController::class, 'store'])
        ->middleware('permission:fault-predictions.create');

    Route::patch('fault-predictions/{fault_prediction}/confirm', [FaultPredictionController::class, 'confirm'])
        ->middleware('permission:fault-predictions.confirm');

    Route::patch('fault-predictions/{fault_prediction}/dismiss', [FaultPredictionController::class, 'dismiss'])
        ->middleware('permission:fault-predictions.dismiss');

    /*
    |----------------------------------------------------------------------
    | Complaints
    |----------------------------------------------------------------------
    */

    Route::prefix('complaints')
        ->name('complaints.')
        ->group(function () {

            Route::get('/', [ComplaintController::class, 'index'])
                ->middleware('permission:complaints.view')
                ->name('index');

            // export لازم تُسجَّل قبل /{complaint} (نفس السبب في faults/export أعلاه).
            Route::get('/export', [ComplaintController::class, 'export'])
                ->middleware('permission:complaints.view')
                ->name('export');

            Route::post('/', [ComplaintController::class, 'store'])
                ->middleware('permission:complaints.create')
                ->name('store');

            Route::get('/{complaint}', [ComplaintController::class, 'show'])
                ->middleware('permission:complaints.view')
                ->name('show');

            Route::patch('/{complaint}/status', [ComplaintController::class, 'updateStatus'])
                ->middleware('permission:complaints.resolve')
                ->name('update-status');

            Route::post('/{complaint}/attachments', [ComplaintController::class, 'storeAttachment'])
                ->middleware('permission:complaints.create')
                ->name('attachments.store');

            Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])
                ->middleware('permission:complaints.delete')
                ->name('destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Offers
    |----------------------------------------------------------------------
    */

    Route::prefix('offers')
        ->name('offers.')
        ->group(function () {

            Route::get('/', [OfferController::class, 'index'])
                ->middleware('permission:offers.view')
                ->name('index');

            Route::post('/', [OfferController::class, 'store'])
                ->middleware('permission:offers.create')
                ->name('store');

            // export لازم تُسجَّل قبل /{offer} (نفس السبب في complaints/export أعلاه).
            Route::get('/export', [OfferController::class, 'export'])
                ->middleware('permission:offers.view')
                ->name('export');

            Route::get('/{offer}', [OfferController::class, 'show'])
                ->middleware('permission:offers.view')
                ->name('show');

            Route::patch('/{offer}', [OfferController::class, 'update'])
                ->middleware('permission:offers.update')
                ->name('update');

            Route::patch('/{offer}/cancel', [OfferController::class, 'cancel'])
                ->middleware('permission:offers.cancel')
                ->name('cancel');

            Route::delete('/{offer}', [OfferController::class, 'destroy'])
                ->middleware('permission:offers.delete')
                ->name('destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Conversations & Messages
    |----------------------------------------------------------------------
    */

    Route::prefix('conversations')
        ->name('conversations.')
        ->group(function () {

            Route::get('/', [ConversationController::class, 'index'])
                ->middleware('permission:conversations.view')
                ->name('index');

            Route::get('/unread-count', [ConversationController::class, 'unreadCount'])
                ->middleware('permission:conversations.view')
                ->name('unread-count');

            Route::post('/', [ConversationController::class, 'store'])
                ->middleware('permission:conversations.start')
                ->name('store');

            Route::post('/start-support', [ConversationController::class, 'startSupport'])
                ->middleware('permission:conversations.start')
                ->name('start-support');

            Route::post('/start-with-owner', [ConversationController::class, 'startWithOwner'])
                ->middleware('permission:conversations.start')
                ->name('start-with-owner');

            Route::get('/{conversation}', [ConversationController::class, 'show'])
                ->middleware('permission:conversations.view')
                ->name('show');

            Route::delete('/{conversation}', [ConversationController::class, 'destroy'])
                ->middleware('permission:conversations.delete')
                ->name('destroy');

            Route::get('/{conversation}/messages', [MessageController::class, 'index'])
                ->middleware('permission:conversations.view')
                ->name('messages.index');

            Route::post('/{conversation}/messages', [MessageController::class, 'store'])
                ->middleware('permission:conversations.send-message')
                ->name('messages.store');

            Route::post('/{conversation}/convert-to-issue', [ConversationController::class, 'convertToIssue'])
                ->middleware('permission:conversations.send-message')
                ->name('convert-to-issue');
        });

    /*
    |----------------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------------
    */

    Route::prefix('notifications')
        ->group(function () {

            Route::get('/', [NotificationController::class, 'index']);

            Route::get('unread-count', [NotificationController::class, 'unreadCount']);

            Route::patch('read-all', [NotificationController::class, 'markAllAsRead']);

            Route::patch('{notification}/read', [NotificationController::class, 'markAsRead']);

            Route::delete('{notification}', [NotificationController::class, 'destroy']);
        });

    /*
    |----------------------------------------------------------------------
    | Users Management
    |----------------------------------------------------------------------
    */

    Route::post('users/generator-owners', [UserController::class, 'storeGeneratorOwner'])
        ->middleware('permission:users.create');

    Route::post('users/subscribers', [UserController::class, 'storeSubscriber'])
        ->middleware('permission:users.create');

    Route::post('users/technicians', [UserController::class, 'storeTechnician'])
        ->middleware('permission:users.create');

    Route::post('users/bulk-payment-reminder', [UserController::class, 'sendBulkPaymentReminder'])
        ->middleware('permission:users.view');

    /*
    |----------------------------------------------------------------------
    | Owner — Subscriber Lookup & Bulk Payment Reminder (نسخ محدودة النطاق
    | من ميزات إدارية، مخصَّصة لمالك المولد نيابةً عن مشتركيه)
    |----------------------------------------------------------------------
    */

    Route::get('owner/subscriber-lookup', [UserController::class, 'ownerSubscriberLookup'])
        ->middleware('role:generator_owner');

    Route::get('owner/subscriber-lookup/{user}/meters', [UserController::class, 'ownerSubscriberMeters'])
        ->middleware('role:generator_owner');

    Route::post('owner/subscribers/bulk-payment-reminder', [UserController::class, 'sendOwnerBulkPaymentReminder'])
        ->middleware(['role:generator_owner', 'permission:payments.view']);

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.view');

    Route::get('users/locked', [UserLockoutController::class, 'lockedIndex'])
        ->middleware('permission:users.unlock');

    Route::get('users/owners-stats', [UserController::class, 'ownersStats']);

    Route::get('users/owners-export', [UserController::class, 'exportOwners']);
    Route::get('users/subscribers-stats', [UserController::class, 'subscribersStats']);
    Route::get('users/subscribers-export', [UserController::class, 'exportSubscribers']);
    // export لازم تُسجَّل قبل /{user} (نفس السبب في faults/export).
    Route::get('users/export', [UserController::class, 'export'])
        ->middleware('permission:users.view');
    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.view');

    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update');

    Route::post('users/{user}/avatar', [UserController::class, 'updateAvatar'])
        ->middleware('permission:users.update');

    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete');

    Route::patch('users/{user}/unlock', [UserLockoutController::class, 'unlock'])
        ->middleware('permission:users.unlock');

    Route::post('users/{user}/send-password-reset-link', [UserController::class, 'sendPasswordResetLink'])
        ->middleware(['permission:users.update', 'throttle:5,1,admin-reset-link']);

    Route::post('users/{user}/set-password', [UserController::class, 'setPassword'])
        ->middleware(['permission:users.update', 'throttle:5,1,admin-set-password']);

    /*
    |----------------------------------------------------------------------
    | Owner Applications — مراجعة طلبات انضمام أصحاب المولدات (أدمن)
    |----------------------------------------------------------------------
    */

    Route::get('admin/owner-applications', [OwnerApplicationController::class, 'index'])
        ->middleware('permission:users.create');

    // ملاحظة: export لازم تُسجَّل قبل show({ownerApplication}) لأن كلاهما
    // GET بنفس عدد الأجزاء (admin/owner-applications/xxx) — لو سُجِّلت بعد
    // show، لاراڤل حيحاول يفسّر "export" كمعرّف مولد (Route Model Binding)
    // فيرجّع 404 بدل ما يوصل فعليًا لدالة export().
    Route::get('admin/owner-applications/export', [OwnerApplicationController::class, 'export'])
        ->middleware('permission:users.create');

    Route::post('admin/owner-applications/bulk-approve', [OwnerApplicationController::class, 'bulkApprove'])
        ->middleware('permission:users.create');

    Route::post('admin/owner-applications/bulk-reject', [OwnerApplicationController::class, 'bulkReject'])
        ->middleware('permission:users.create');

    Route::get('admin/owner-applications/{ownerApplication}', [OwnerApplicationController::class, 'show'])
        ->middleware('permission:users.create');

    Route::post('admin/owner-applications/{ownerApplication}/approve', [OwnerApplicationController::class, 'approve'])
        ->middleware('permission:users.create');

    Route::post('admin/owner-applications/{ownerApplication}/reject', [OwnerApplicationController::class, 'reject'])
        ->middleware('permission:users.create');

    Route::patch('admin/owner-applications/{ownerApplication}/internal-note', [OwnerApplicationController::class, 'updateInternalNote'])
        ->middleware('permission:users.create');

    /*
    |----------------------------------------------------------------------
    | Activity Logs
    |----------------------------------------------------------------------
    */

    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:users.view');

    /*
    |----------------------------------------------------------------------
    | Neighborhood Dashboard
    |----------------------------------------------------------------------
    */

    Route::get('neighborhoods/dashboard', [NeighborhoodDashboardController::class, 'index'])
        ->middleware('permission:neighborhoods.dashboard');
});
Route::get('live-schedule', [LiveScheduleController::class, 'index'])->name('live-schedule');

Route::get('platform-identity', [SettingController::class, 'publicIndex'])
    ->name('platform-identity');

/*
|----------------------------------------------------------------------
|    خريطة تغطية المولدات لصفحة الهبوط
|----------------------------------------------------------------------
*/
Route::get('public/generators-map', [PublicGeneratorsMapController::class, 'index'])
    ->name('public.generators-map');

Route::get('public/generators-list', [PublicGeneratorsListController::class, 'index'])
    ->name('public.generators-list');

Route::get('public/platform-stats', [PublicPlatformStatsController::class, 'index'])
    ->name('public.platform-stats');

Route::get('public/platform-analytics', [PublicAnalyticsController::class, 'index'])
    ->name('public.platform-analytics');

Route::post('public/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.contact-messages.store');

Route::post('public/guest-login', [GuestDemoLoginController::class, 'login'])
    ->middleware('throttle:20,1')
    ->name('public.guest-login');
