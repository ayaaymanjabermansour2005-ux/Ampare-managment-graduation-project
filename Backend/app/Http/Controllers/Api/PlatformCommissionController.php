<?php

namespace App\Http\Controllers\Api;

use App\Exports\PlatformCommissionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformCommission\UpdatePlatformCommissionStatusRequest;
use App\Http\Resources\PlatformCommissionResource;
use App\Models\PlatformCommission;
use App\Models\User;
use App\Services\Pdf\PlatformCommissionReportPdfService;
use App\Services\PlatformCommissionService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  		عمولات المنصة
 */
class PlatformCommissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PlatformCommissionService $commissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PlatformCommission::class);

        $commissions = $this->commissionService->list(
            $request->user(),
            PerPageResolver::resolve($request)
        );

        return $this->success(
            message: 'قائمة العمولات.',
            data: PlatformCommissionResource::collection($commissions)->response()->getData(true)
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PlatformCommission::class);

        return $this->success(
            message: 'إجماليات العمولات.',
            data: $this->commissionService->summary($request->user())
        );
    }

    public function updateStatus(UpdatePlatformCommissionStatusRequest $request, PlatformCommission $platformCommission): JsonResponse
    {
        $this->authorize('updateStatus', $platformCommission);

        $commission = $this->commissionService->markPaid($platformCommission);

        return $this->success(
            message: 'تم اعتماد تحويل العمولة بنجاح.',
            data: new PlatformCommissionResource($commission)
        );
    }

    public function downloadReportPdf(
        Request $request,
        PlatformCommissionReportPdfService $pdfService
    ): Response {
        $user = $request->user();
        $ownerId = $user->isAdmin() ? $request->integer('owner_id') : $user->id;

        if ($user->isAdmin() && ! $ownerId) {
            throw ValidationException::withMessages([
                'owner_id' => ['يجب تحديد owner_id عند طلب التقرير كأدمن.'],
            ]);
        }

        $owner = User::findOrFail($ownerId);
        return $pdfService->download($owner, $request->input('from'), $request->input('to'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', PlatformCommission::class);

        return Excel::download(
            new PlatformCommissionsExport(
                $request->user(),
                $request->input('from'),
                $request->input('to')
            ),
            'commissions-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
