<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Actions\Technician\CreateTechnicianUserAction;
use App\Actions\User\AdminCreateSubscriberAction;
use App\Actions\User\AdminSetUserPasswordAction;
use App\Actions\User\CreateGeneratorOwnerAction;
use App\Actions\User\SendBulkPaymentReminderAction;
use App\Actions\User\UpdateOwnerCommissionSettingsAction;
use App\DTOs\Technician\CreateTechnicianUserData;
use App\DTOs\User\AdminCreateSubscriberData;
use App\DTOs\User\CreateGeneratorOwnerData;
use App\Enums\CommissionMode;
use App\Enums\Role;
use App\Exports\GeneratorOwnersExport;
use App\Exports\SubscribersExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Technician\AdminCreateTechnicianRequest;
use App\Http\Requests\User\AdminCreateSubscriberRequest;
use App\Http\Requests\User\AdminSetUserPasswordRequest;
use App\Http\Requests\User\SendBulkPaymentReminderRequest;
use App\Http\Requests\User\SendOwnerBulkPaymentReminderRequest;
use App\Http\Requests\User\StoreGeneratorOwnerRequest;
use App\Http\Requests\User\UpdateAvatarRequest;
use App\Http\Requests\User\UpdateOwnerCommissionSettingsRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\OwnerSubscriberLookupResource;
use App\Http\Resources\OwnerSubscriberMeterLookupResource;
use App\Http\Resources\TechnicianResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  				إدارة المستخدمين وسجل التدقيق
 */
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list(
            $request->input('role'),
            $request->input('search'),
            PerPageResolver::resolve($request),
            $request->input('status'),
            $request->input('subscription_status')
        );

        return $this->success(
            message: 'قائمة المستخدمين.',
            data: UserResource::collection($users)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', User::class);

        return Excel::download(
            new UsersExport(
                $request->input('role'),
                $request->input('search'),
                $request->input('status'),
                $request->input('subscription_status')
            ),
            'users-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function ownersStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->success(
            message: 'إحصائيات أصحاب المولدات.',
            data: $this->userService->ownersStats(
                $request->integer('year') ?: null,
                $request->input('period', '6')
            )
        );
    }

    public function exportOwners(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', User::class);

        return Excel::download(
            new GeneratorOwnersExport(
                $request->input('search'),
                $request->input('status')
            ),
            'generator-owners-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function subscribersStats(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->success(
            message: 'إحصائيات المشتركين.',
            data: $this->userService->subscribersStats()
        );
    }

    public function exportSubscribers(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', User::class);

        return Excel::download(
            new SubscribersExport(
                $request->input('search'),
                $request->input('status')
            ),
            'subscribers-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(
            message: 'بيانات المستخدم.',
            data: new UserResource($user->load('roles'))
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $updated = $this->userService->update($user, $request->validated());

        return $this->success(
            message: 'تم تحديث بيانات المستخدم بنجاح.',
            data: new UserResource($updated)
        );
    }

    public function updateAvatar(UpdateAvatarRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $updated = $this->userService->updateAvatar($user, $request->file('avatar'));

        return $this->success(
            message: 'تم تحديث الصورة الشخصية بنجاح.',
            data: new UserResource($updated)
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return $this->success(message: 'تم حذف المستخدم بنجاح.');
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور من قبل الأدمن لصالح مستخدم آخر —
     * تعيد استخدام نفس الإجراء المستخدَم بمسار "نسيت كلمة المرور" العام،
     * فقط بصلاحية أدمن بدل طلب ذاتي من صاحب الحساب.
     */
    public function sendPasswordResetLink(User $user, SendPasswordResetLinkAction $action, Request $request): JsonResponse
    {
        $admin = $request->user();

        if (! $admin instanceof User) {
            abort(401);
        }

        abort_unless($admin->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $action->execute($user->email);

        return $this->success(
            message: "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريد {$user->email}."
        );
    }

    /**
     * تعيين كلمة سر مؤقتة مباشرة من قبل الأدمن — بديل احتياطي لرابط البريد،
     * مخصَّص لحالات تعذّر وصول البريد للمستخدم (بريد خاطئ، مشاكل تسليم...).
     * تُبطَل كل جلسات المستخدم الحالية فور التعيين، والإجراء مسجَّل بالكامل.
     */
    public function setPassword(
        AdminSetUserPasswordRequest $request,
        User $user,
        AdminSetUserPasswordAction $action
    ): JsonResponse {
        $admin = $request->user();

        if (! $admin instanceof User) {
            abort(401);
        }

        abort_unless($admin->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $action->execute($user, $request->validated('password'), $admin);

        return $this->success(
            message: 'تم تعيين كلمة سر مؤقتة بنجاح. يرجى إبلاغ المستخدم بها عبر قناة تواصل موثوقة (هاتف مثلاً).'
        );
    }

    public function storeGeneratorOwner(
        StoreGeneratorOwnerRequest $request,
        CreateGeneratorOwnerAction $action
    ): JsonResponse {
        $this->authorize('create', User::class);

        $owner = $action->execute(CreateGeneratorOwnerData::fromArray($request->validated()), $request->user());

        return $this->success(
            message: 'تم إنشاء حساب صاحب المولد بنجاح.',
            data: new UserResource($owner),
            code: 201
        );
    }

    public function storeSubscriber(
        AdminCreateSubscriberRequest $request,
        AdminCreateSubscriberAction $action
    ): JsonResponse {
        $admin = $request->user();

        if (! $admin instanceof User) {
            abort(401);
        }

        abort_unless($admin->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $user = $action->execute(AdminCreateSubscriberData::fromArray($request->validated()), $admin);

        return $this->success(
            message: 'تم إنشاء حساب المشترك بنجاح.',
            data: new UserResource($user),
            code: 201
        );
    }

    public function storeTechnician(
        AdminCreateTechnicianRequest $request,
        CreateTechnicianUserAction $action
    ): JsonResponse {
        $admin = $request->user();

        if (! $admin instanceof User) {
            abort(401);
        }

        abort_unless($admin->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $owner = User::findOrFail((int) $request->validated('owner_id'));
        $technician = $action->execute(CreateTechnicianUserData::fromArray($request->validated()), $owner, $admin);

        return $this->success(
            message: "تم إنشاء حساب الفني بنجاح، تابع لمالك المولد \"{$owner->name}\".",
            data: new TechnicianResource($technician),
            code: 201
        );
    }

    public function updateCommissionSettings(
        UpdateOwnerCommissionSettingsRequest $request,
        User $user,
        UpdateOwnerCommissionSettingsAction $action
    ): JsonResponse {
        $this->authorize('manageCommissionSettings', $user);

        $updated = $action->execute(
            $user,
            CommissionMode::from($request->validated('commission_mode')),
            $request->validated('commission_rate') !== null
                ? (float) $request->validated('commission_rate')
                : null,
            $request->user()
        );

        return $this->success(
            message: 'تم تحديث إعدادات العمولة بنجاح.',
            data: new UserResource($updated)
        );
    }

    public function sendBulkPaymentReminder(
        SendBulkPaymentReminderRequest $request,
        SendBulkPaymentReminderAction $action
    ): JsonResponse {
        $admin = $request->user();

        if (! $admin instanceof User) {
            abort(401);
        }

        abort_unless($admin->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        // ملاحظة: subscriber_ids تُعاد تصفيتها داخل passedValidation() عبر
        // merge()، وهذا التعديل لا ينعكس على validated() (تُبنى من لقطة
        // الـ Validator الأصلية قبل التصفية) — لذلك نقرأ input() هنا عمدًا.
        $remindedInvoicesCount = $action->execute($request->input('subscriber_ids'));

        return $this->success(
            message: $remindedInvoicesCount > 0
                ? "تم إرسال تذكير بشأن {$remindedInvoicesCount} فاتورة معلّقة/متأخرة."
                : 'لا توجد فواتير معلّقة أو متأخرة للمشتركين المحدَّدين حاليًا.',
            data: ['reminded_invoices_count' => $remindedInvoicesCount]
        );
    }

    /**
     * تذكير دفع جماعي من قِبَل مالك المولد — يعيد استخدام نفس
     * SendBulkPaymentReminderAction المستخدَم بمسار الأدمن، لكن قائمة
     * subscriber_ids هنا سبق تصفيتها في SendOwnerBulkPaymentReminderRequest
     * لتقتصر على مشتركين لهم اشتراك فعلي على أحد مولدات هذا المالك.
     */
    public function sendOwnerBulkPaymentReminder(
        SendOwnerBulkPaymentReminderRequest $request,
        SendBulkPaymentReminderAction $action
    ): JsonResponse {
        $this->authorize('sendBulkPaymentReminderAsOwner', User::class);

        // نفس الملاحظة: subscriber_ids المُصفّاة تُقرأ عبر input() لا
        // validated() (انظر sendBulkPaymentReminder أعلاه لنفس السبب).
        $remindedInvoicesCount = $action->execute($request->input('subscriber_ids'));

        return $this->success(
            message: $remindedInvoicesCount > 0
                ? "تم إرسال تذكير بشأن {$remindedInvoicesCount} فاتورة معلّقة/متأخرة."
                : 'لا توجد فواتير معلّقة أو متأخرة للمشتركين المحدَّدين ضمن مولداتك حاليًا.',
            data: ['reminded_invoices_count' => $remindedInvoicesCount]
        );
    }

    /**
     * بحث محدود عن مستخدمين بدور "مشترك" (id/name/email/phone فقط) — لخطوة
     * "إضافة اشتراك" من طرف مالك المولد. مقصود أن تكون ضيّقة الحقول
     * ومحدودة النتائج (أقصى 20)، بخلاف index() الإدارية الكاملة.
     */
    public function ownerSubscriberLookup(Request $request): JsonResponse
    {
        $this->authorize('lookupForOwner', User::class);

        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', Role::SUBSCRIBER->value));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return $this->success(
            message: 'نتائج البحث عن المشتركين.',
            data: OwnerSubscriberLookupResource::collection($subscribers)
        );
    }

    /**
     * عدادات مشترك محدَّد (id/meter_number/property_label/status فقط) —
     * تُستخدم لملء قائمة اختيار العداد بعد اختيار المشترك في خطوة "إضافة
     * اشتراك" من طرف مالك المولد. اختيار العداد نفسه لا يكشف شيئًا حسّاسًا
     * (مجرد معرِّفات عداد فعلية)، لذا نفس قدرة lookupForOwner تكفي.
     */
    public function ownerSubscriberMeters(User $user): JsonResponse
    {
        $this->authorize('lookupForOwner', User::class);

        abort_unless($user->hasRole(Role::SUBSCRIBER->value), 404);

        $meters = $user->subscriber
            ? $user->subscriber->meters()->orderBy('meter_number')->get(['id', 'subscriber_id', 'meter_number', 'property_label', 'status'])
            : collect();

        return $this->success(
            message: 'عدادات المشترك.',
            data: OwnerSubscriberMeterLookupResource::collection($meters)
        );
    }
}
