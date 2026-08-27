<?php

namespace App\Services;

use App\Enums\OperatingSchedule;
use App\Models\Generator;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Support\Scheduling\ScheduleWindow;
use Illuminate\Validation\ValidationException;

class GeneratorCapacityService
{
    public function assertNoDuplicateContract(
        Generator $generator,
        SubscriberMeter $meter,
        OperatingSchedule $schedule,
        ?string $serviceStartTime,
        ?string $serviceEndTime,
        ?int $excludeSubscriptionId = null
    ): void {
        $query = Subscription::where('subscriber_meter_id', $meter->id)
            ->where('generator_id', $generator->id)
            ->where('schedule', $schedule)
            ->whereIn('status', Subscription::DUPLICATE_BLOCKING_STATUSES);

        if ($schedule === OperatingSchedule::Custom) {
            $query->where('service_start_time', $serviceStartTime)
                ->where('service_end_time', $serviceEndTime);
        }

        if ($excludeSubscriptionId) {
            $query->where('id', '!=', $excludeSubscriptionId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'generator_id' => ['يوجد عقد مطابق بالفعل لنفس العداد ونفس نوع الفترة مع هذا المولد.'],
            ])->status(409);
        }
    }

    public function assertCapacityAvailable(
        Generator $generator,
        OperatingSchedule $schedule,
        ?string $serviceStartTime,
        ?string $serviceEndTime,
        float $requestedCapacityKw,
        ?int $excludeSubscriptionId = null
    ): void {
        if ($generator->capacity_kw === null) {
            return;
        }

        $newSegments = ScheduleWindow::segmentsFor($schedule, $serviceStartTime, $serviceEndTime);

        $reserved = $this->reservedCapacityOverlapping($generator, $newSegments, $excludeSubscriptionId);

        if (($reserved + $requestedCapacityKw) > (float) $generator->capacity_kw) {
            $available = max(0, (float) $generator->capacity_kw - $reserved);

            throw ValidationException::withMessages([
                'requested_capacity_kw' => [
                    "القدرة المتاحة لهذا المولد بهذه الفترة الزمنية هي {$available} كيلوواط فقط، وهي أقل من القدرة المطلوبة ({$requestedCapacityKw}).",
                ],
            ])->status(422);
        }
    }

    public function reservedCapacityOverlapping(Generator $generator, array $newSegments, ?int $excludeSubscriptionId = null): float
    {
        $query = Subscription::where('generator_id', $generator->id)
            ->whereIn('status', Subscription::CAPACITY_RESERVING_STATUSES);

        if ($excludeSubscriptionId) {
            $query->where('id', '!=', $excludeSubscriptionId);
        }

        $reserved = 0.0;

        $query->get(['id', 'schedule', 'service_start_time', 'service_end_time', 'requested_capacity_kw'])
            ->each(function (Subscription $existing) use (&$reserved, $newSegments) {
                $existingSegments = ScheduleWindow::segmentsFor(
                    $existing->schedule,
                    $existing->service_start_time,
                    $existing->service_end_time
                );

                if (ScheduleWindow::overlaps($newSegments, $existingSegments)) {
                    $reserved += (float) $existing->requested_capacity_kw;
                }
            });

        return $reserved;
    }

    public function canAccept(Generator $generator, SubscriberMeter $meter, OperatingSchedule $schedule, ?string $start, ?string $end, float $requestedCapacityKw): bool
    {
        $duplicateExists = Subscription::where('subscriber_meter_id', $meter->id)
            ->where('generator_id', $generator->id)
            ->where('schedule', $schedule)
            ->whereIn('status', Subscription::DUPLICATE_BLOCKING_STATUSES)
            ->when($schedule === OperatingSchedule::Custom, fn ($q) => $q->where('service_start_time', $start)->where('service_end_time', $end))
            ->exists();

        if ($duplicateExists) {
            return false;
        }

        if ($generator->capacity_kw === null) {
            return true;
        }

        $newSegments = ScheduleWindow::segmentsFor($schedule, $start, $end);
        $reserved = $this->reservedCapacityOverlapping($generator, $newSegments);

        return ($reserved + $requestedCapacityKw) <= (float) $generator->capacity_kw;
    }
}
