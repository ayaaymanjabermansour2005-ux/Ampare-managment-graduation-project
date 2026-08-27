<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! preg_match('/^[0-9a-f-]{36}$/i', $key)) {
            return response()->json([
                'success' => false,
                'message' => 'رأس Idempotency-Key مطلوب (UUID صالح) لهذه العملية.',
                'data' => null,
                'errors' => null,
            ], 422);
        }

        $lock = cache()->lock("idempotency:lock:{$key}", 10);

        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه العملية قيد المعالجة حاليًا، يرجى الانتظار قليلًا.',
                'data' => null,
                'errors' => null,
            ], 409);
        }

        try {
            $existing = IdempotencyKey::find($key);

            if ($existing) {
                // FIX (أمان): المفتاح مرتبط بمستخدم محدد — يُرفض لأي مستخدم آخر.
                if ($request->user() && (int) $existing->user_id !== (int) $request->user()->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'رأس Idempotency-Key هذا غير صالح لهذا الطلب.',
                        'data' => null,
                        'errors' => null,
                    ], 409);
                }

                // FIX (صحّة): المفتاح مرتبط بمسار محدد — إعادة استخدامه على
                // endpoint مختلف كانت تُرجع استجابة العملية القديمة الخاطئة.
                // الآن نرفض إعادة الاستخدام عبر مسارات مختلفة صراحةً.
                if ($existing->route !== $request->path()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'رأس Idempotency-Key هذا مستخدم مسبقًا على عملية مختلفة.',
                        'data' => null,
                        'errors' => null,
                    ], 409);
                }

                return response()->json(
                    $existing->response_body,
                    $existing->response_status
                );
            }

            $response = $next($request);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && $request->user()) {
                IdempotencyKey::create([
                    'key' => $key,
                    'user_id' => $request->user()->id,
                    'route' => $request->path(),
                    'response_status' => $response->getStatusCode(),
                    'response_body' => json_decode($response->getContent(), true),
                ]);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
