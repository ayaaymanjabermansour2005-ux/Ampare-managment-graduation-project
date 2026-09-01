<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * مثل الـcast المدمج 'encrypted'، لكن لا يُسقط الطلب بالكامل إذا تعذّر فك
 * تشفير قيمة قديمة (مثلاً بعد تدوير APP_KEY) — يُرجع null بدل رمي
 * DecryptException ("The MAC is invalid.") التي كانت تُفشل صفحة "طرق
 * الدفع" بالكامل بسبب سجل واحد فاسد.
 */
class SafelyEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning("Failed to decrypt {$key} on ".$model::class."#{$model->getKey()}: {$e->getMessage()}");

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString($value);
    }
}
