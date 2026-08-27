<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AccountLockoutService
{
    public function isLocked(User $user): bool
    {
        return $user->locked_until !== null && $user->locked_until->isFuture();
    }

    public function registerFailedAttempt(User $user): void
    {
        $maxAttempts = config('auth.lockout.max_attempts', 5);

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts >= $maxAttempts) {
            $this->lock($user);
        }
    }

    public function lock(User $user): void
    {
        $this->decayLockoutCountIfStale($user);

        $durations = config('auth.lockout.durations', [15, 30, 60, 240]);
        $index = min($user->lockout_count, count($durations) - 1);
        $minutes = $durations[$index];

        $user->forceFill([
            'locked_until' => now()->addMinutes($minutes),
            'lockout_count' => $user->lockout_count + 1,
            'last_locked_at' => now(),
            'failed_login_attempts' => 0,
        ])->save();

        activity()
            ->performedOn($user)
            ->withProperties([
                'minutes' => $minutes,
                'lockout_count' => $user->lockout_count,
            ])
            ->log('account_locked');
    }

    public function resetOnSuccessfulLogin(User $user): void
    {
        if ($user->failed_login_attempts > 0 || $user->lockout_count > 0 || $user->locked_until !== null) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'lockout_count' => 0,
                'locked_until' => null,
            ])->save();
        }
    }

    public function unlock(User $user, User $admin): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'lockout_count' => 0,
            'locked_until' => null,
        ])->save();

        activity()
            ->causedBy($admin)
            ->performedOn($user)
            ->log('account_unlocked_by_admin');
    }

    public function lockedAccounts(): Collection
    {
        return User::query()
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderByDesc('last_locked_at')
            ->get();
    }

    private function decayLockoutCountIfStale(User $user): void
    {
        $decayHours = config('auth.lockout.decay_hours', 24);

        if (
            $user->last_locked_at !== null
            && $user->last_locked_at->lt(now()->subHours($decayHours))
        ) {
            $user->lockout_count = 0;
        }
    }
}
