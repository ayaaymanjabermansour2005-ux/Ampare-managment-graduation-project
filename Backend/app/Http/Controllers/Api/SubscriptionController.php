<?php

namespace App\Http\Controllers\Api;

use App\Actions\Subscription\TransferSubscriptionAction;
use App\Exports\SubscriptionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreOwnerSubscriptionRequest;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\TransferSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionStatusRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Generator;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Services\Pdf\SubscriptionContractPdfService;
use App\Services\SubscriptionService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	 	الاشتراكات وطلبات الخدمة
 */
class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $subscriptions = $this->subscriptionService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->query('search'),
            $request->query('status')
        );

        return $this->success(
            message: 'قائمة الاشتراكات.',
            data: SubscriptionResource::collection($subscriptions)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Subscription::class);

        return Excel::download(
            new SubscriptionsExport(
                $request->user(),
                $request->query('search'),
                $request->query('status')
            ),
            'subscriptions-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('create', Subscription::class);

        $subscription = $this->subscriptionService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم إرسال طلب الاشتراك بنجاح، بانتظار موافقة مالك المولد.',
            data: new SubscriptionResource($subscription->load(['subscriberMeter.subscriber.neighborhood', 'subscriberMeter.subscriber.user', 'generator.owner'])),
            code: 201
        );
    }

    /**
     * إنشاء اشتراك من قِبَل مالك المولد نيابةً عن مشترك — نظير store() لكن
     * لمسار المالك (بدل الاشتراك الذاتي للمشترك). نطاق الصلاحية الحقيقي
     * (المولد المستهدَف يجب أن يكون مملوكًا لصاحب الحساب الحالي) يُتحقَّق
     * منه في StoreOwnerSubscriptionRequest::withValidator() وأيضًا هنا عبر
     * SubscriptionPolicy::createByOwner (دفاع بعمق، بنفس نمط بقية الكود).
     */
    public function storeByOwner(StoreOwnerSubscriptionRequest $request, SubscriptionService $service): JsonResponse
    {
        $generator = Generator::findOrFail($request->validated('generator_id'));

        $this->authorize('createByOwner', [Subscription::class, $generator]);

        $meter = SubscriberMeter::findOrFail($request->validated('subscriber_meter_id'));

        $subscription = $service->createForOwner($request->validated(), $request->user(), $meter);

        return $this->success(
            message: 'تم إنشاء الاشتراك بنجاح.',
            data: new SubscriptionResource($subscription->load(['subscriberMeter.subscriber.neighborhood', 'subscriberMeter.subscriber.user', 'generator.owner'])),
            code: 201
        );
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        return $this->success(
            message: 'بيانات الاشتراك.',
            data: new SubscriptionResource($subscription->load(['subscriberMeter.subscriber.user', 'generator.owner']))
        );
    }

    public function updateStatus(UpdateSubscriptionStatusRequest $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('updateStatus', $subscription);

        $subscription = $this->subscriptionService->updateStatus($subscription, $request->validated()['status']);

        return $this->success(
            message: 'تم تحديث حالة الاشتراك بنجاح.',
            data: new SubscriptionResource($subscription)
        );
    }

    public function downloadContractPdf(
        Subscription $subscription,
        SubscriptionContractPdfService $pdfService
    ): Response {
        $this->authorize('view', $subscription);

        return $pdfService->download($subscription);
    }

    public function transfer(TransferSubscriptionRequest $request, Subscription $subscription, TransferSubscriptionAction $action): JsonResponse
    {
        $this->authorize('transfer', $subscription);

        $newGenerator = Generator::findOrFail($request->validated('generator_id'));

        $subscription = $action->execute($subscription, $newGenerator, $request->user());

        return $this->success(
            message: 'تم نقل الاشتراك بنجاح.',
            data: new SubscriptionResource($subscription)
        );
    }

    /**
     * نقل اشتراك بين مولدات مالك المولد نفسه — نظير transfer() (المخصَّص
     * للأدمن) لكن لمسار المالك. الـ TransferSubscriptionRequest مُعاد
     * استخدامها كما هي (شكل الحقول مطابق: generator_id فقط)؛ القيد الإضافي
     * — أن المولد الهدف مملوك لنفس المالك أيضًا — يُنفَّذ داخل
     * TransferSubscriptionAction ضمن نفس الـ transaction (بعد lockForUpdate
     * على المولدين) عبر تمرير $ownerScope، وليس مكررًا هنا.
     */
    public function transferByOwner(TransferSubscriptionRequest $request, Subscription $subscription, TransferSubscriptionAction $action): JsonResponse
    {
        $this->authorize('transferByOwn', $subscription);

        $newGenerator = Generator::findOrFail($request->validated('generator_id'));

        $subscription = $action->execute($subscription, $newGenerator, $request->user(), $request->user());

        return $this->success(
            message: 'تم نقل الاشتراك بنجاح.',
            data: new SubscriptionResource($subscription)
        );
    }
}
