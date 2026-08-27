<?php

namespace App\Support\TechnicianTask;

use App\Models\Generator;
use App\Models\Technician;
use Illuminate\Validation\ValidationException;

class TechnicianEligibilityChecker
{
    public function assertEligible(Technician $technician, Generator $generator): void
    {
        if ($technician->owner_id !== $generator->owner_id) {
            throw ValidationException::withMessages([
                'technician_id' => ['هذا الفني غير مرتبط بهذا المولد.'],
            ]);
        }

        $hasExplicitLinks = $technician->generators()->exists();

        if ($hasExplicitLinks && ! $technician->generators()->where('generators.id', $generator->id)->exists()) {
            throw ValidationException::withMessages([
                'technician_id' => ['هذا الفني غير مرتبط بهذا المولد تحديدًا.'],
            ]);
        }
    }
}
