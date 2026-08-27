<?php

namespace App\Http\Controllers\Api;

use App\Enums\FaultRepairMethod;
use App\Enums\FaultStatus;
use App\Exports\FaultsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fault\DecideFaultRepairRequest;
use App\Http\Requests\Fault\StoreFaultRequest;
use App\Http\Requests\Fault\UpdateFaultStatusRequest;
use App\Http\Requests\Fault\VerifyFaultRequest;
use App\Http\Resources\FaultResource;
use App\Models\Fault;
use App\Services\FaultService;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group 	  			الأعطال والتوقعات الذكية
 */
class FaultController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FaultService $faultService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Fault::class);

        $faults = $this->faultService->list(
            $request->user(),
            PerPageResolver::resolve($request),
            $request->input('search'),
            $request->input('status')
        );

        return $this->success(
            message: 'قائمة الأعطال.',
            data: FaultResource::collection($faults)->response()->getData(true)
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Fault::class);

        return Excel::download(
            new FaultsExport(
                $request->user(),
                $request->input('search'),
                $request->input('status')
            ),
            'faults-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(StoreFaultRequest $request): JsonResponse
    {
        $this->authorize('create', Fault::class);

        $fault = $this->faultService->create($request->validated(), $request->user());

        return $this->success(
            message: 'تم تسجيل العطل بنجاح، بانتظار تحقق مالك المولد.',
            data: new FaultResource($fault->load('generator')),
            code: 201
        );
    }

    public function show(Fault $fault): JsonResponse
    {
        $this->authorize('view', $fault);

        return $this->success(
            message: 'بيانات العطل.',
            data: new FaultResource($fault->load(['generator', 'verifiedBy', 'closedBy', 'prediction', 'technicianTasks']))
        );
    }

    public function verify(VerifyFaultRequest $request, Fault $fault): JsonResponse
    {
        $this->authorize('verify', $fault);

        $fault = $this->faultService->verify($fault, $request->user(), $request->boolean('is_valid'));

        return $this->success(
            message: $fault->status->value === FaultStatus::Verified->value ? 'تم تأكيد صحة العطل.' : 'تم رفض البلاغ.',
            data: new FaultResource($fault)
        );
    }

    public function decideRepair(DecideFaultRepairRequest $request, Fault $fault): JsonResponse
    {
        $this->authorize('decideRepair', $fault);

        $fault = $this->faultService->decideRepair(
            $fault,
            $request->user(),
            FaultRepairMethod::from($request->validated('repair_method')),
            $request->validated('technician_id'),
            $request->validated('instructions'),
        );

        return $this->success(
            message: 'تم تسجيل قرار الإصلاح.',
            data: new FaultResource($fault->load('technicianTasks'))
        );
    }

    public function overrideStatus(UpdateFaultStatusRequest $request, Fault $fault): JsonResponse
    {
        $this->authorize('overrideStatus', $fault);

        $fault = $this->faultService->overrideStatus(
            $fault,
            FaultStatus::from($request->validated('status')),
            $request->validated('admin_override_reason')
        );

        return $this->success(
            message: 'تم تجاوز حالة العطل يدويًا من قِبل الأدمن.',
            data: new FaultResource($fault->load('generator'))
        );
    }

    public function destroy(Fault $fault): JsonResponse
    {
        $this->authorize('delete', $fault);

        $this->faultService->delete($fault);

        return $this->success(message: 'تم حذف العطل بنجاح.');
    }
}
