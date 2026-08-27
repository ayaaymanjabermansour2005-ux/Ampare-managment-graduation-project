<?php

namespace App\Http\Controllers\Api;

use App\Exports\OffersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offer\StoreOfferRequest;
use App\Http\Requests\Offer\UpdateOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Services\OfferService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  				العروض والخصومات
 */
class OfferController extends Controller
{
    use ApiResponse;

    public function __construct(protected OfferService $offerService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Offer::class);

        $offers = $this->offerService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->boolean('include_expired'),
            $request->input('search')
        );

        return $this->success(
            message: 'قائمة العروض.',
            data: OfferResource::collection($offers)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Offer::class);

        return Excel::download(
            new OffersExport(
                $request->user(),
                $request->query('search'),
                $request->boolean('include_expired', true)
            ),
            'offers-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $this->authorize('create', Offer::class);

        $offer = $this->offerService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم إنشاء العرض بنجاح.',
            data: new OfferResource($offer),
            code: 201
        );
    }

    public function show(Offer $offer): JsonResponse
    {
        $this->authorize('view', $offer);

        $offer->load(['owner', 'targetedSubscribers']);

        return $this->success(
            message: 'تفاصيل العرض.',
            data: new OfferResource($offer)
        );
    }

    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $this->authorize('update', $offer);

        $updated = $this->offerService->update($offer, $request->validated());

        return $this->success(
            message: 'تم تحديث العرض.',
            data: new OfferResource($updated)
        );
    }

    public function cancel(Offer $offer): JsonResponse
    {
        $this->authorize('cancel', $offer);

        $cancelled = $this->offerService->cancel($offer);

        return $this->success(
            message: 'تم إلغاء العرض.',
            data: new OfferResource($cancelled)
        );
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return $this->success(message: 'تم حذف العرض بنجاح.');
    }
}
