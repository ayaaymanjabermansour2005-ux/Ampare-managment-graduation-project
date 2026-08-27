<?php

namespace App\Services;

class FakePaymentGatewayService
{
    private const DECLINE_CARD = '4000000000000002';
    private const INSUFFICIENT_FUNDS_CARD = '4000000000009995';
    private const EXPIRED_CARD = '4000000000000069';

    /**
     * @return array{success: bool, transaction_reference: ?string, decline_reason: ?string}
     */
    public function charge(string $cardNumber, string $expiryMonth, string $expiryYear, string $cvv): array
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);

        if (! $this->passesLuhnCheck($cardNumber)) {
            return $this->declined('رقم البطاقة غير صحيح.');
        }

        if ($this->isExpired((int) $expiryMonth, (int) $expiryYear)) {
            return $this->declined('البطاقة منتهية الصلاحية.');
        }

        if ($cardNumber === self::DECLINE_CARD) {
            return $this->declined('تم رفض العملية من البنك المُصدر للبطاقة.');
        }

        if ($cardNumber === self::INSUFFICIENT_FUNDS_CARD) {
            return $this->declined('الرصيد غير كافٍ لإتمام العملية.');
        }

        if ($cardNumber === self::EXPIRED_CARD) {
            return $this->declined('البطاقة منتهية الصلاحية.');
        }

        return [
            'success' => true,
            'transaction_reference' => 'GTW-' . strtoupper(bin2hex(random_bytes(6))),
            'decline_reason' => null,
        ];
    }

    private function isExpired(int $month, int $year): bool
    {
        if ($month < 1 || $month > 12) {
            return true;
        }

        $fullYear = $year < 100 ? 2000 + $year : $year;

        return now()->greaterThan(now()->createFromDate($fullYear, $month, 1)->endOfMonth());
    }

    private function passesLuhnCheck(string $number): bool
    {
        if (! ctype_digit($number) || strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    private function declined(string $reason): array
    {
        return [
            'success' => false,
            'transaction_reference' => null,
            'decline_reason' => $reason,
        ];
    }
}
