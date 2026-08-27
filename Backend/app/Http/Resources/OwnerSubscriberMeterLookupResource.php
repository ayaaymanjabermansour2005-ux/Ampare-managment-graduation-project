<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * حقول مصغَّرة (معرِّفات العداد فقط، بدون بيانات المشترك) لقائمة اختيار
 * العداد في خطوة "إضافة اشتراك" من طرف مالك المولد.
 */
class OwnerSubscriberMeterLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meter_number' => $this->meter_number,
            'property_label' => $this->property_label,
            'status' => $this->status,
        ];
    }
}
