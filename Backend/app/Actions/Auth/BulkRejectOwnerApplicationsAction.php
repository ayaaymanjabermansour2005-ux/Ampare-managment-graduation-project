<?php

namespace App\Actions\Auth;

use App\Models\OwnerApplication;
use App\Models\User;
use App\Policies\OwnerApplicationPolicy;
use Illuminate\Validation\ValidationException;

final class BulkRejectOwnerApplicationsAction
{
    public function __construct(
        private readonly RejectOwnerApplicationAction $rejectAction,
        private readonly OwnerApplicationPolicy $policy,
    ) {}

    /**
     * @param  array<int>  $applicationIds
     * @return array{rejected: array<int>, failed: array<int, string>}
     */
    public function execute(array $applicationIds, User $reviewer, ?string $reason = null): array
    {
        $rejected = [];
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
                $this->rejectAction->execute($application, $reviewer, $reason);
                $rejected[] = $id;
            } catch (\Throwable $e) {
                $failed[$id] = $e instanceof ValidationException
                    ? collect($e->errors())->flatten()->first()
                    : 'تعذّر رفض هذا الطلب.';
            }
        }

        return ['rejected' => $rejected, 'failed' => $failed];
    }
}
