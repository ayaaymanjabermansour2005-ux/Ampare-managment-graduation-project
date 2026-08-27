<?php

namespace App\Services;

use App\Models\Generator;
use App\Models\GeneratorDiagnosticReading;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GeneratorDiagnosticService
{
    public function record(Generator $generator, array $data, User $user): GeneratorDiagnosticReading
    {
        return GeneratorDiagnosticReading::create([
            ...$data,
            'generator_id' => $generator->id,
            'recorded_by' => $user->id,
        ]);
    }

    public function history(Generator $generator, int $limit = 20): Collection
    {
        return GeneratorDiagnosticReading::where('generator_id', $generator->id)
            ->latest('reading_date')
            ->limit($limit)
            ->get();
    }
}
