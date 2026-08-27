<?php

namespace App\Http\Controllers\Api;

use App\Exports\LoginLogsExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoginLogController extends Controller
{
    use ApiResponse;

    public function export(Request $request): BinaryFileResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        return Excel::download(
            new LoginLogsExport(
                $request->user(),
                $request->query('search'),
                $request->query('event')
            ),
            'login-logs-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $event = $request->query('event');
        $search = $request->query('search');

        $logs = Activity::query()
            ->when(
                in_array($event, ['login_succeeded', 'login_failed'], true),
                fn ($query) => $query->where('description', $event),
                fn ($query) => $query->whereIn('description', ['login_succeeded', 'login_failed'])
            )
            ->when($search, fn ($query) => $query->whereHasMorph(
                'subject',
                [User::class],
                fn ($subQuery) => $subQuery->where('name', 'like', "%{$search}%")
            ))
            ->with('subject')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return $this->success(
            message: 'سجل تسجيل الدخول.',
            data: $logs->through(fn ($log) => [
                'id' => $log->id,
                'event' => $log->description,
                'user_name' => $log->subject?->name ?? 'غير معروف',
                'ip' => $log->properties['ip'] ?? null,
                'created_at' => $log->created_at->toDateTimeString(),
            ])
        );
    }
}
