<?php

namespace App\Services;

use App\Enums\FaultPredictionStatus;
use App\Enums\FaultPriority;
use App\Enums\FaultSource;
use App\Enums\FaultStatus;
use App\Models\Fault;
use App\Models\FaultPrediction;
use App\Models\Generator;
use App\Models\User;
use App\Support\Eloquent\FreshOrFail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaultPredictionService
{
    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = FaultPrediction::query()->with('generator');

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('generator', fn (Builder $q) => $q->where('owner_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): FaultPrediction
    {
        return FaultPrediction::create([
            ...$data,
            'is_actual_fault' => false,
            'status' => FaultPredictionStatus::Pending->value,
        ]);
    }

    public function confirm(FaultPrediction $prediction, User $confirmedBy): Fault
    {
        return DB::transaction(function () use ($prediction, $confirmedBy) {
            $prediction = FaultPrediction::lockForUpdate()->findOrFail($prediction->id);

            if ($prediction->status !== FaultPredictionStatus::Pending) {
                throw ValidationException::withMessages([
                    'prediction' => ['تم اتخاذ قرار بخصوص هذا التوقع مسبقًا.'],
                ]);
            }

            $generator = Generator::findOrFail($prediction->generator_id);

            $fault = Fault::create([
                'generator_id' => $generator->id,
                'fault_prediction_id' => $prediction->id,
                'source' => FaultSource::AiPrediction->value,
                'title' => 'عطل متوقع: '.$prediction->prediction_type,
                'description' => $prediction->recommendation ?? 'تم اكتشافه عبر نظام التوقع الذكي.',
                'priority' => FaultPriority::High->value,
                'status' => FaultStatus::Verified,
                'verified_by' => $confirmedBy->id,
                'verified_at' => now(),
                'reported_at' => now(),
            ]);

            $prediction->update([
                'is_actual_fault' => true,
                'status' => FaultPredictionStatus::Confirmed->value,
            ]);

            return $fault;
        });
    }

    public function dismiss(FaultPrediction $prediction): FaultPrediction
    {
        return DB::transaction(function () use ($prediction) {
            $prediction = FaultPrediction::lockForUpdate()->findOrFail($prediction->id);

            if ($prediction->status !== FaultPredictionStatus::Pending) {
                throw ValidationException::withMessages([
                    'prediction' => ['تم اتخاذ قرار بخصوص هذا التوقع مسبقًا.'],
                ]);
            }

            $prediction->update([
                'is_actual_fault' => false,
                'status' => FaultPredictionStatus::Dismissed->value,
            ]);

            return FreshOrFail::reload($prediction);
        });
    }
}
