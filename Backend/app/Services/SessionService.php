<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SessionService
{
    public function isSupported(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * @return Collection<int, array{id: mixed, ip_address: mixed, user_agent: mixed, last_activity: string, is_current: bool}>
     */
    public function activeSessions(Request $request, User $user): Collection
    {
        $currentId = $request->hasSession() ? $request->session()->getId() : null;

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_activity' => now()->createFromTimestamp($row->last_activity)->toDateTimeString(),
                'is_current' => $row->id === $currentId,
            ]);
    }

    public function revokeSession(User $user, string $sessionId): bool
    {
        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return (bool) $deleted;
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, event: string, ip: mixed, created_at: ?string}>
     */
    public function loginLogFor(User $user, int $perPage = 30): LengthAwarePaginator
    {
        return Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereIn('description', ['login_succeeded', 'login_failed'])
            ->latest()
            ->paginate($perPage)
            ->through(fn ($log) => [
                'id' => $log->id,
                'event' => $log->description,
                'ip' => $log->properties['ip'] ?? null,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);
    }
}
