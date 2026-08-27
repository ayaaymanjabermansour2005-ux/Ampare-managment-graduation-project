<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventGuestDemoMutations
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isMutatingRequest = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($user && $user->is_guest_demo && $isMutatingRequest) {
            return $this->error(
                message: 'هاي نسخة تجريبية للاستعراض بس — التعديلات الفعلية غير متاحة. سجّلي حساب حقيقي عشان تقدري تحفظي بياناتك.',
                code: 403
            );
        }

        return $next($request);
    }
}
