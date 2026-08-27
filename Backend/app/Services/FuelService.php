<?php

namespace App\Services;

use App\Enums\Currency;
use App\Events\FuelStockLow;
use App\Models\FuelPurchase;
use App\Models\FuelReading;
use App\Models\Generator;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FuelService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService,
        protected AttachmentService $attachmentService
    ) {}

    public function purchases(Generator $generator): Collection
    {
        return $generator->fuelPurchases()->with('recorder')->latest('purchased_at')->get();
    }

    public function readings(Generator $generator): Collection
    {
        return $generator->fuelReadings()->with('recorder')->latest('reading_date')->get();
    }

    public function recordPurchase(Generator $generator, array $data, User $user): FuelPurchase
    {
        [$exchangeRate, $costIls] = $this->exchangeRateService->toIls(
            (float) $data['cost_amount'],
            Currency::from($data['currency'])
        );

        return DB::transaction(function () use ($generator, $data, $user, $exchangeRate, $costIls) {
            $purchase = FuelPurchase::create([
                'generator_id' => $generator->id,
                'recorded_by' => $user->id,
                'liters' => $data['liters'],
                'cost_amount' => $data['cost_amount'],
                'currency' => $data['currency'],
                'exchange_rate' => $exchangeRate,
                'cost_amount_ils' => $costIls,
                'purchased_at' => $data['purchased_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['attachments'])) {
                $this->attachmentService->storeMany($purchase, $data['attachments'], $user);
            }

            $generator->forceFill(['last_low_fuel_alert_at' => null])->save();

            return $purchase->fresh(['recorder', 'attachments']);
        });
    }

    public function recordReading(Generator $generator, array $data, User $user): FuelReading
    {
        return DB::transaction(function () use ($generator, $data, $user) {
            $reading = FuelReading::create([
                'generator_id' => $generator->id,
                'recorded_by' => $user->id,
                'tank_level_liters' => $data['tank_level_liters'],
                'meter_hours' => $data['meter_hours'] ?? null,
                'reading_date' => $data['reading_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['attachments'])) {
                $this->attachmentService->storeMany($reading, $data['attachments'], $user);
            }

            $status = $this->checkLowFuelLevel($generator);

            if ($status && $status['is_low'] && ! $this->alreadyAlertedToday($generator)) {
                FuelStockLow::dispatch($generator, $status);
                $generator->forceFill(['last_low_fuel_alert_at' => now()])->save();
            }

            return $reading->fresh(['recorder', 'attachments']);
        });
    }

    public function consumptionBetween(Generator $generator, string $from, string $to): ?array
    {
        $startReading = FuelReading::where('generator_id', $generator->id)
            ->where('reading_date', '<=', $from)
            ->orderByDesc('reading_date')
            ->first();

        $endReading = FuelReading::where('generator_id', $generator->id)
            ->where('reading_date', '<=', $to)
            ->orderByDesc('reading_date')
            ->first();

        if (! $startReading || ! $endReading || $startReading->id === $endReading->id) {
            return null;
        }

        $purchasedLiters = (float) FuelPurchase::where('generator_id', $generator->id)
            ->whereBetween('purchased_at', [$startReading->reading_date, $endReading->reading_date])
            ->sum('liters');

        $rawConsumption = (float) $startReading->tank_level_liters
            + $purchasedLiters
            - (float) $endReading->tank_level_liters;

        $isAnomalous = $rawConsumption < 0;

        return [
            'start_date' => $startReading->reading_date->toDateString(),
            'end_date' => $endReading->reading_date->toDateString(),
            'start_level_liters' => (float) $startReading->tank_level_liters,
            'end_level_liters' => (float) $endReading->tank_level_liters,
            'purchased_liters' => round($purchasedLiters, 2),
            'consumed_liters' => $isAnomalous ? 0.0 : round($rawConsumption, 2),
            'is_anomalous' => $isAnomalous,
            'anomaly_note' => $isAnomalous
                ? 'مستوى الخزان بنهاية الفترة أكبر من (مستوى البداية + الكمية المشتراة) — يوجد خطأ محتمل بالإدخال، يرجى مراجعة القراءات.'
                : null,
        ];
    }

    public function costPerKwh(Generator $generator, string $from, string $to): ?array
    {
        $consumption = $this->consumptionBetween($generator, $from, $to);

        if (! $consumption || $consumption['is_anomalous']) {
            return null;
        }

        $fuelCostIls = (float) FuelPurchase::where('generator_id', $generator->id)
            ->whereBetween('purchased_at', [$consumption['start_date'], $consumption['end_date']])
            ->sum('cost_amount_ils');

        $producedKw = (float) MeterReading::whereHas(
            'subscription',
            fn($q) => $q->where('generator_id', $generator->id)
        )
            ->whereBetween('reading_date', [$consumption['start_date'], $consumption['end_date']])
            ->sum('consumed_kw');

        if ($producedKw <= 0) {
            return null;
        }

        return [
            ...$consumption,
            'fuel_cost_ils' => round($fuelCostIls, 2),
            'produced_kw' => round($producedKw, 2),
            'cost_per_kwh_ils' => round($fuelCostIls / $producedKw, 3),
        ];
    }

    /**
     * @param  FuelReading|null  $preloadedReading     
     */
    public function checkLowFuelLevel(Generator $generator, ?FuelReading $preloadedReading = null): ?array
    {
        if (! $generator->tank_capacity_liters) {
            return null;
        }

        $latestReading = $preloadedReading ?? $generator->fuelReadings()->latest('reading_date')->first();

        if (! $latestReading) {
            return null;
        }

        $percentage = round(
            ((float) $latestReading->tank_level_liters / (float) $generator->tank_capacity_liters) * 100,
            1
        );

        return [
            'reading_date' => $latestReading->reading_date->toDateString(),
            'current_level_liters' => (float) $latestReading->tank_level_liters,
            'capacity_liters' => (float) $generator->tank_capacity_liters,
            'percentage' => $percentage,
            'is_low' => $percentage < 20,
        ];
    }

    private function alreadyAlertedToday(Generator $generator): bool
    {
        return $generator->last_low_fuel_alert_at?->isToday() ?? false;
    }
}