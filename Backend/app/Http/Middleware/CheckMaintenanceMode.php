<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = in_array(
            Setting::get('maintenance_mode_enabled', '0'),
            ['1', 1, true, 'true'],
            true
        );

        if (! $enabled) {
            return $next($request);
        }

        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        $message = Setting::get('maintenance_message') ?: 'المنصة قيد الصيانة حاليًا، يرجى المحاولة لاحقًا.';

        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
        ], 503);
    }
}
