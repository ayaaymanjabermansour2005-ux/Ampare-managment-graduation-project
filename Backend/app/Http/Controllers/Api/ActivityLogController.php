<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\OwnerApplication;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PlatformCommission;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use App\Support\PerPageResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Spatie\Activitylog\Models\Activity;

/**
 * @group 	  				إدارة المستخدمين وسجل التدقيق
 */
class ActivityLogController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            'auth:sanctum',
            'permission:activity-logs.view',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with('causer')
            ->latest();

        if ($request->filled('subject_type')) {
            $map = [
                'invoice' => Invoice::class,
                'payment' => Payment::class,
                'subscription' => Subscription::class,
                'generator' => Generator::class,
                'platform_commission' => PlatformCommission::class,
                'payment_method' => PaymentMethod::class,
                'technician' => Technician::class,
                'technician_task' => TechnicianTask::class,
                'user' => User::class,
                'owner_application' => OwnerApplication::class,
            ];

            $subjectClass = $map[$request->input('subject_type')] ?? null;

            $query->when(
                $subjectClass,
                fn ($q) => $q->where('subject_type', $subjectClass)
                    // subject_id غير مقبول إلا مقترنًا بـ subject_type محدَّد ومعروف،
                    // تفاديًا لتسريب سجلّات نماذج أخرى عبر نفس المعرّف الرقمي.
                    ->when(
                        $request->filled('subject_id'),
                        fn ($qq) => $qq->where('subject_id', $request->integer('subject_id'))
                    )
            );
        }

        if ($request->filled('causer_id')) {
            $query->where(
                'causer_id',
                $request->integer('causer_id')
            );
        }

        if ($request->filled('from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('from')
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('to')
            );
        }

        $perPage = PerPageResolver::resolve($request, default: 25);

        $logs = $query->paginate($perPage);

        return $this->success(
            message: 'سجل التدقيق.',
            data: $logs->through(
                fn (Activity $activity) => [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => $activity->subject_type
                        ? class_basename($activity->subject_type)
                        : null,
                    'subject_id' => $activity->subject_id,

                    'causer' => $activity->causer
                        ? [
                            'id' => $activity->causer->id,
                            'name' => $activity->causer->name,
                        ]
                        : null,

                    'changes' => $activity->changes(),

                    'created_at' => $activity->created_at
                        ? $activity->created_at->toDateTimeString()
                        : null,
                ]
            )
        );
    }
}
