<?php

namespace App\Services;

use App\Models\GeneratorSchedule;
use Illuminate\Support\Collection;

class LiveScheduleService
{
    public function activeNowByNeighborhood(?int $neighborhoodId = null): Collection
    {
        $query = GeneratorSchedule::query()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['generator.location.neighborhood']);

        if ($neighborhoodId) {
            $query->whereHas('generator.location', fn ($q) => $q->where('neighborhood_id', $neighborhoodId));
        }

        return $query->get()->map(fn ($schedule) => [
            'generator_name' => $schedule->generator->name,
            'neighborhood' => $schedule->generator->location?->neighborhood?->name,
            'starts_at' => $schedule->starts_at->format('H:i'),
            'ends_at' => $schedule->ends_at->format('H:i'),
        ]);
    }

    public function upcomingByNeighborhood(?int $neighborhoodId = null): Collection
    {
        $query = GeneratorSchedule::query()
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(12))
            ->with(['generator.location.neighborhood'])
            ->orderBy('starts_at');

        if ($neighborhoodId) {
            $query->whereHas('generator.location', fn ($q) => $q->where('neighborhood_id', $neighborhoodId));
        }

        return $query->get()->map(fn ($schedule) => [
            'generator_name' => $schedule->generator->name,
            'neighborhood' => $schedule->generator->location?->neighborhood?->name,
            'starts_at' => $schedule->starts_at->format('H:i'),
            'ends_at' => $schedule->ends_at->format('H:i'),
        ]);
    }
}
