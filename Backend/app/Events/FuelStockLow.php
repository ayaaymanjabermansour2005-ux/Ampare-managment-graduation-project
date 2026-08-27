<?php

namespace App\Events;

use App\Models\Generator;
use Illuminate\Foundation\Events\Dispatchable;

class FuelStockLow
{
    use Dispatchable;

    public function __construct(public Generator $generator, public array $status) {}
}
