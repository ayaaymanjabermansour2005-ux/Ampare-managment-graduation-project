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

                // IDEMPOTENCY-payload: إعادة استخدام نفس المفتاح ببيانات
                // مختلفة عن أول مرة كانت تُرجع نتيجة الطلب الأول بصمت (بدون
                // خطأ)، بدون معالجة الطلب الجديد إطلاقًا — سلوك مربك لو صار
                // فعليًا. صفوف قديمة بلا payload_hash (قبل هذا التعديل)
                // تُعامَل كحالة غير معروفة فتُعرض كما كانت، لا تُرفض كذبًا.
                if ($existing->payload_hash !== null && $existing->payload_hash !== $this->computePayloadHash($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'رأس Idempotency-Key هذا استُخدم مسبقًا ببيانات مختلفة عن الطلب الحالي.',
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
                    'payload_hash' => $this->computePayloadHash($request),
                    'response_status' => $response->getStatusCode(),
                    'response_body' => json_decode($response->getContent(), true),
                ]);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * بصمة مستقرة لمحتوى الطلب (حقول + ملفات) تُستخدم لاكتشاف إعادة استخدام
     * نفس المفتاح ببيانات مختلفة. الملفات تُمثَّل باسمها الأصلي + حجمها +
     * بصمة محتواها الفعلي (لا اسم الملف وحده)، بدل قراءة كامل بايتات الطلب
     * الخام (اللي بيختلف بسبب multipart boundaries حتى لو المحتوى الفعلي
     * متطابق).
     */
    private function computePayloadHash(Request $request): string
    {
        $normalize = function ($value) use (&$normalize) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                return [
                    '__file__' => true,
                    'name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'hash' => $value->isValid() ? md5_file($value->getRealPath()) : null,
                ];
            }

            if (is_array($value)) {
                return array_map($normalize, $value);
            }

            return $value;
        };

        return hash('sha256', json_encode($normalize($request->all())));
    }
}
