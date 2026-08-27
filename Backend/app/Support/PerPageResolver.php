<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PerPageResolver
{
    public const DEFAULT_MAX = 100;

    private function __construct()
    {
        // كائن Static فقط عمدًا.
    }

    public static function resolve(Request $request, int $default = 15, int $max = self::DEFAULT_MAX): int
    {
        return max(1, min($request->integer('per_page', $default), $max));
    }
}
