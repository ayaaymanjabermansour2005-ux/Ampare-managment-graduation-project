<?php

namespace App\Actions\Auth;

use App\Models\OwnerApplication;
use App\Models\User;
use App\Policies\OwnerApplicationPolicy;
use Illuminate\Validation\ValidationException;

final class BulkApproveOwnerApplicationsAction
{
    public function __construct(
        private readonly ApproveOwnerApplicationAction $approveAction,
        private readonly OwnerApplicationPolicy $policy,
    ) {}

    /**
     * يعالج كل طلب على حدة (نفس منطق القبول الفردي بالضبط، بما فيه القفل
     * الذري وإنشاء المالك/المولد داخل معاملة مستقلة لكل طلب)، بحيث فشل
     * طلب واحد (مثلًا سبق مراجعته) لا يوقف باقي الدفعة.
     *
     * @param  array<int>  $applicationIds
     * @return array{approved: array<int>, failed: array<int, string>}
     */
    public function execute(array $applicationIds, User $reviewer): array
    {
        $approved = [];
        $failed = [];

        $applications = OwnerApplication::whereIn('id', $applicationIds)->get()->keyBy('id');

        foreach ($applicationIds as $id) {
            $application = $applications->get($id);

            if (! $application) {
                $failed[$id] = 'الطلب غير موجود.';

                continue;
            }

            if (! $this->policy->review($reviewer, $application)) {
                $failed[$id] = 'لا تملك صلاحية مراجعة هذا الطلب أو أنه سبق أن رُوجع.';

                continue;
            }

            try {
                $this->approveAction->execute($application, $reviewer);
                $approved[] = $id;
            } catch (\Throwable $e) {
                $failed[$id] = $e instanceof ValidationException
                    ? collect($e->errors())->flatten()->first()
                    : 'تعذّر قبول هذا الطلب.';
            }
        }

        return ['approved' => $approved, 'failed' => $failed];
    }
}
