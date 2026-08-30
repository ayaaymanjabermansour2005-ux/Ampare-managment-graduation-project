<?php

namespace App\Actions\Auth;

use App\Enums\OwnerApplicationStatus;
use App\Enums\Role;
use App\Models\Location;
use App\Models\OwnerApplication;
use App\Models\User;
use App\Notifications\OwnerApplicationApprovedNotification;
use App\Services\GeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveOwnerApplicationAction
{
    public function __construct(private readonly GeneratorService $generatorService) {}

    public function execute(OwnerApplication $application, User $reviewer): OwnerApplication
    {
        return DB::transaction(function () use ($application, $reviewer) {
            $application = OwnerApplication::lockForUpdate()->findOrFail($application->id);

            if (! $application->isPending()) {
                throw ValidationException::withMessages([
                    'status' => ['هذا الطلب سبق أن تمت مراجعته.'],
                ]);
            }

            $owner = User::create([
                'name' => $application->name,
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => $application->getRawOriginal('password'),
            ]);

            $owner->forceFill(['email_verified_at' => now()])->save();
            $owner->assignRole(Role::GENERATOR_OWNER->value);

            $location = Location::create([
                'neighborhood_id' => $application->generator_neighborhood_id,
                'city' => $application->generator_city,
                'address' => $application->generator_address,
                'latitude' => $application->generator_latitude,
                'longitude' => $application->generator_longitude,
            ]);

            $this->generatorService->create([
                'name' => $application->generator_name,
                'price_per_kw' => (float) $application->generator_price_per_kw,
                'currency' => $application->generator_currency->value,
                'capacity_kw' => $application->generator_capacity_kw,
                'location_id' => $location->id,
                'operating_schedule' => '24h',
            ], $owner);

            $application->forceFill([
                'status' => OwnerApplicationStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => null,
                'created_user_id' => $owner->id,
            ])->save();

            activity()
                ->causedBy($reviewer)
                ->performedOn($owner)
                ->withProperties(['owner_application_id' => $application->id])
                ->log('owner_application_approved');

            $owner->notify(new OwnerApplicationApprovedNotification);

            return $application->fresh(['reviewedBy', 'createdUser']);
        });
    }
}
