<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * حقول مصغَّرة عمدًا (id/name/email/phone فقط) لخطوة "بحث عن مشترك" في
 * إضافة اشتراك من طرف مالك المولد — لا تُستخدم UserResource الكاملة هنا
 * حتى لا تتسرَّب بيانات إدارية للمالك عن مستخدم لا تربطه به أي علاقة بعد.
 */
class OwnerSubscriberLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
