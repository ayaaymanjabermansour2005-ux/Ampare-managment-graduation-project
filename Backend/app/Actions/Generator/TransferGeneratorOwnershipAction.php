<?php

namespace App\Actions\Generator;

use App\Models\Generator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TransferGeneratorOwnershipAction
{
    public function execute(Generator $generator, User $newOwner, User $admin): Generator
    {
        return DB::transaction(function () use ($generator, $newOwner, $admin) {
            /** @var Generator $generator */
            $generator = Generator::lockForUpdate()->findOrFail($generator->id);

            $previousOwnerId = $generator->owner_id;

            $generator->update(['owner_id' => $newOwner->id]);

            activity()
                ->performedOn($generator)
                ->causedBy($admin)
                ->withProperties([
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwner->id,
                ])
                ->log('generator_ownership_transferred');

            return $generator->fresh(['owner', 'location']);
        });
    }
}
