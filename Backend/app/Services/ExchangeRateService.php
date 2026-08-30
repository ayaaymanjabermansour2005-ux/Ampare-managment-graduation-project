<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExchangeRateService
{
    public function usdToIls(): ?float
    {
        return Cache::remember('exchange_rate:usd_ils', now()->addMinutes(5), function () {
            try {
                $response = Http::timeout(5)
                    ->retry(2, 200)
                    ->get(config('services.exchange_rate.base_url').'/latest', [
                        'from' => 'USD',
                        'to' => 'ILS',
                    ]);

                if (! $response->successful()) {
                    Log::warning('ExchangeRateService: استجابة غير ناجحة من مزوّد سعر الصرف.', [
                        'status' => $response->status(),
                    ]);

                    return $this->fallbackRate();
                }

                $rate = $response->json('rates.ILS');

                if (! is_numeric($rate) || $rate <= 0) {
                    Log::warning('ExchangeRateService: استجابة بدون سعر صرف صالح.', [
                        'body' => $response->body(),
                    ]);

                    return $this->fallbackRate();
                }

                return Money::round((float) $rate, 4);
            } catch (\Throwable $e) {
                Log::warning('ExchangeRateService: تعذّر جلب سعر الصرف.', [
                    'message' => $e->getMessage(),
                ]);

                return $this->fallbackRate();
            }
        });
    }

    private function fallbackRate(): ?float
    {
        $manual = Setting::get('fallback_usd_ils_rate');

        if (! is_numeric($manual) || (float) $manual <= 0) {
            return null;
        }

        Log::info('ExchangeRateService: استُخدم سعر الصرف الاحتياطي اليدوي.', ['rate' => $manual]);

        return Money::round((float) $manual, 4);
    }

    /**
     * @return array{0: ?float, 1: float}
     *
     * @throws ValidationException
     */
    public function toIls(float $amount, Currency $currency): array
    {
        if ($currency === Currency::ILS) {
            return [null, $amount];
        }

        $rate = $this->usdToIls();

        if ($rate === null) {
            throw ValidationException::withMessages([
                'currency' => ['تعذّر الوصول لسعر الصرف حاليًا. لا يمكن إتمام العملية بالدولار الآن، حاول لاحقًا أو استخدم الشيكل.'],
            ]);
        }

        return [$rate, Money::mul($amount, $rate, 2)];
    }
}
