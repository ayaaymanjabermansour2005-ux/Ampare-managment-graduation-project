<?php

namespace App\Services;

use App\Models\TechnicianPayment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TechnicianPaymentService
{
    public function list(User $user, int $perPage = 15, ?int $technicianId = null, ?string $status = null): LengthAwarePaginator
    {
        $query = TechnicianPayment::query()->with(['technician.user', 'owner', 'paymentMethod', 'reviewedBy']);

        if ($user->isAdmin()) {
            // بلا نطاق إضافي — عرض/تدقيق فقط، بدون صلاحية تعديل الحالة.
        } elseif ($user->isOwner()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->whereHas('technician', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }
}
