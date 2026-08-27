<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pdf\OwnerMonthlyReportPdfService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group التقرير الشهري لصاحب المولد
 */
class OwnerMonthlyReportController extends Controller
{
    use ApiResponse;

    public function downloadPdf(Request $request, OwnerMonthlyReportPdfService $pdfService): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $ownerId = $request->integer('owner_id');

            if (! $ownerId) {
                abort(422, 'يجب تحديد owner_id عند طلب التقرير كأدمن.');
            }
        } else {
            $requestedOwnerId = $request->integer('owner_id');

            if ($requestedOwnerId && $requestedOwnerId !== $user->id) {
                abort(403);
            }

            $ownerId = $user->id;
        }

        $owner = User::findOrFail($ownerId);

        $month = $request->filled('month')
            ? now()->parse($request->input('month'))
            : now()->subMonthNoOverflow();

        return $pdfService->download($owner, $month);
    }
}
