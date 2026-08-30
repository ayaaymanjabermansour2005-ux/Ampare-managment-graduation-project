<?php

namespace App\Services;

use App\Enums\FaultStatus;
use App\Enums\GeneratorStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Fault;
use App\Models\Neighborhood;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NeighborhoodDashboardService
{
    public function summary(): Collection
    {
        $neighborhoods = Neighborhood::query()
            ->withCount([
                'generators',
                'generators as active_generators_count' => fn ($q) => $q->where('status', GeneratorStatus::Active->value),
            ])
            ->get();

        $activeSubscriptionsByNeighborhood = Subscription::query()
            ->join('generators', 'generators.id', '=', 'subscriptions.generator_id')
            ->join('locations', 'locations.id', '=', 'generators.location_id')
            ->where('subscriptions.status', SubscriptionStatus::Active->value)
            ->whereNotNull('locations.neighborhood_id')
            ->groupBy('locations.neighborhood_id')
            ->select('locations.neighborhood_id', DB::raw('count(*) as total'))
            ->pluck('total', 'neighborhood_id');

        $openFaultsByNeighborhood = Fault::query()
            ->join('generators', 'generators.id', '=', 'faults.generator_id')
            ->join('locations', 'locations.id', '=', 'generators.location_id')
            ->whereNotIn('faults.status', [FaultStatus::Resolved->value, FaultStatus::Rejected->value])
            ->whereNotNull('locations.neighborhood_id')
            ->groupBy('locations.neighborhood_id')
            ->select('locations.neighborhood_id', DB::raw('count(*) as total'))
            ->pluck('total', 'neighborhood_id');

        return $neighborhoods
            ->map(fn (Neighborhood $neighborhood) => [
                'neighborhood_id' => $neighborhood->id,
                'neighborhood_name' => $neighborhood->name,
                'generators_count' => $neighborhood->generators_count,
                'active_generators_count' => $neighborhood->active_generators_count,
                'active_subscriptions_count' => (int) ($activeSubscriptionsByNeighborhood[$neighborhood->id] ?? 0),
                'open_faults_count' => (int) ($openFaultsByNeighborhood[$neighborhood->id] ?? 0),
            ])
            ->values();
    }
}
