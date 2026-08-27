<?php

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberNormalizer
{
    private const DEFAULT_REGION = 'PS';

    public function normalize(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($raw, self::DEFAULT_REGION);
        } catch (NumberParseException) {
            return $raw;
        }

        if (! $util->isValidNumber($parsed)) {
            return $raw;
        }

        return $util->format($parsed, PhoneNumberFormat::E164);
    }
}
