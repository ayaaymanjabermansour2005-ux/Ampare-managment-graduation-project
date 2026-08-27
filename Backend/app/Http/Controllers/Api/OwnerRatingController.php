<?php

namespace App\Http\Controllers\Api;

use App\Actions\OwnerRating\RateOwnerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerRating\StoreOwnerRatingRequest;
use App\Http\Resources\OwnerRatingResource;
use App\Models\OwnerRating;
use App\Models\Subscription;
use App\Models\User;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group تقييمات أصحاب المولدات
 */
class OwnerRatingController extends Controller
{
    use ApiResponse;

    public function store(StoreOwnerRatingRequest $request, Subscription $subscription, RateOwnerAction $action): JsonResponse
    {
        $rating = $action->execute(
            $subscription,
            $request->validated('rating'),
            $request->validated('comment'),
            $request->user()
        );

        return $this->success(
            message: 'تم إرسال تقييمك لصاحب المولد بنجاح.',
            data: new OwnerRatingResource($rating->load('rater')),
            code: 201
        );
    }

    public function index(Request $request, User $owner): JsonResponse
    {
        $this->authorize('viewAny', [OwnerRating::class, $owner]);

        $ratings = OwnerRating::query()
            ->where('owner_id', $owner->id)
            ->with('rater')
            ->latest()
            ->paginate(PerPageResolver::resolve($request));

        return $this->success(
            message: 'تقييمات صاحب المولد.',
            data: [
                'ratings' => OwnerRatingResource::collection($ratings)->response()->getData(true),
                'average_rating' => round((float) OwnerRating::where('owner_id', $owner->id)->avg('rating'), 2),
                'ratings_count' => OwnerRating::where('owner_id', $owner->id)->count(),
            ]
        );
    }
}
