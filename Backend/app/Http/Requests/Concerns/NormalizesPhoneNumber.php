<?php

namespace App\Http\Requests\Concerns;

use App\Services\PhoneNumberNormalizer;

trait NormalizesPhoneNumber
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => app(PhoneNumberNormalizer::class)->normalize($this->input('phone')),
            ]);
        }
    }
}
