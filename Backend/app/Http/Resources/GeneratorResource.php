<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => sprintf('GEN-%03d', $this->id),
            'name' => $this->name,
            'name_en' => $this->name_en,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'price_per_kw' => (float) $this->price_per_kw,
            'currency' => $this->currency,
            'capacity_kw' => $this->capacity_kw,
            'lines_count' => $this->lines_count,
            'fuel_type' => $this->fuel_type?->value,
            'fuel_type_label' => $this->fuel_type?->label(),
            'notes' => $this->notes,
            'tank_capacity_liters' => $this->tank_capacity_liters ? (float) $this->tank_capacity_liters : null,
            'rated_voltage' => $this->rated_voltage,
            'rated_frequency_hz' => $this->rated_frequency_hz,
            'phase_count' => $this->phase_count,
            'rated_load_kw' => $this->rated_load_kw,
            'service_interval_hours' => $this->service_interval_hours,
            'next_service_due_at' => $this->next_service_due_at?->format('Y-m-d'),
            'installed_at' => $this->installed_at?->format('Y-m-d'),
            'fuel_percentage' => $this->fuelPercentage(),
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id,
                'city' => $this->location->city,
                'neighborhood' => $this->location->neighborhood?->name,
                'address' => $this->location->address,
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude,
            ]),
            'status' => $this->status,
            'verified_by' => $this->whenLoaded('verifier', fn () => $this->verifier ? [
                'id' => $this->verifier->id,
                'name' => $this->verifier->name,
            ] : null),
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'operating_schedule' => $this->operating_schedule,
            'operating_start_time' => $this->operating_start_time,
            'operating_end_time' => $this->operating_end_time,
            'owner' => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ],
            'active_subscriptions_count' => $this->whenCounted('subscriptions'),
            'monthly_revenue_ils' => $this->when(
                $this->monthly_revenue_ils !== null,
                fn () => (float) $this->monthly_revenue_ils
            ),
            'last_maintenance_at' => $this->whenLoaded(
                'latestMaintenanceTask',
                fn () => $this->latestMaintenanceTask?->created_at?->toDateTimeString()
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    private function fuelPercentage(): ?float
    {
        if (! $this->relationLoaded('latestFuelReading')) {
            return null;
        }

        if (! $this->tank_capacity_liters || ! $this->latestFuelReading) {
            return null;
        }

        return round(
            ((float) $this->latestFuelReading->tank_level_liters / (float) $this->tank_capacity_liters) * 100,
            1
        );
    }
}
