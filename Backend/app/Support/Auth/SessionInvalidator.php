<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionInvalidator
{
    public function invalidateAllFor(User $user, bool $keepCurrent = false): SessionInvalidationResult
    {
        if (config('session.driver') !== 'database') {
            Log::warning(
                'Session invalidation skipped: SESSION_DRIVER must be "database" for this feature to work.',
                [
                    'user_id' => $user->id,
                    'current_driver' => config('session.driver'),
                ]
            );

            return SessionInvalidationResult::unsupported();
        }

        $query = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id);

        if ($keepCurrent && request()->hasSession()) {
            $query->where('id', '!=', request()->session()->getId());
        }

        return SessionInvalidationResult::success($query->delete());
    }
}
