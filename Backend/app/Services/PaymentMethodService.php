<?php

namespace App\Services;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    public function list(User $user, int $perPage = 15, ?string $search = null, ?int $technicianId = null): LengthAwarePaginator
    {
        $query = PaymentMethod::query()->with('owner');

        if ($technicianId) {
            $technician = Technician::findOrFail($technicianId);

            $authorized = $user->isAdmin()
                || ($user->isOwner() && $technician->owner_id === $user->id)
                || $technician->user_id === $user->id;

            if (! $authorized) {
                throw ValidationException::withMessages([
                    'technician_id' => ['لا يمكنك الاطلاع على وسائل دفع هذا الفني.'],
                ]);
            }

            $query->where('user_id', $technician->user_id);
        } elseif (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->whereHas('owner', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, User $user): PaymentMethod
    {
        $type = $data['type'] instanceof PaymentMethodType
            ? $data['type']
            : PaymentMethodType::from($data['type']);

        $this->assertAccountDetailsPresent($type, $data);
        $this->assertCurrencyConsistent($type, $data['currency'] ?? null);

        return DB::transaction(function () use ($data, $user, $type) {
            if (! empty($data['is_default'])) {
                PaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
            }

            return PaymentMethod::create([
                'user_id' => $user->id,
                'type' => $type,
                'is_default' => $data['is_default'] ?? false,
                'currency' => $type->requiresCurrency() ? ($data['currency'] ?? null) : null,
                'bank_name' => $data['bank_name'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
            ]);
        });
    }

    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        $type = isset($data['type'])
            ? ($data['type'] instanceof PaymentMethodType ? $data['type'] : PaymentMethodType::from($data['type']))
            : $paymentMethod->type;

        $this->assertAccountDetailsPresent($type, [
            'bank_name' => $data['bank_name'] ?? $paymentMethod->bank_name,
            'account_name' => $data['account_name'] ?? $paymentMethod->account_name,
            'account_number' => $data['account_number'] ?? $paymentMethod->account_number,
        ]);

        $resolvedCurrency = $type->requiresCurrency()
            ? (array_key_exists('currency', $data) ? $data['currency'] : $paymentMethod->currency)
            : null;

        $this->assertCurrencyConsistent($type, $resolvedCurrency);

        return DB::transaction(function () use ($paymentMethod, $data, $type, $resolvedCurrency) {
            if (! empty($data['is_default'])) {
                PaymentMethod::where('user_id', $paymentMethod->user_id)
                    ->where('id', '!=', $paymentMethod->id)
                    ->update(['is_default' => false]);
            }

            $paymentMethod->update([
                'type' => $type,
                'is_default' => $data['is_default'] ?? $paymentMethod->is_default,
                'currency' => $resolvedCurrency,
                'bank_name' => $data['bank_name'] ?? $paymentMethod->bank_name,
                'account_name' => $data['account_name'] ?? $paymentMethod->account_name,
                'account_number' => $data['account_number'] ?? $paymentMethod->account_number,
            ]);

            return $paymentMethod->fresh();
        });
    }

    public function delete(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->delete();
    }

    private function assertAccountDetailsPresent(PaymentMethodType $type, array $data): void
    {
        if (! $type->requiresAccountDetails()) {
            return;
        }

        $missing = array_filter(
            ['bank_name', 'account_name', 'account_number'],
            fn(string $field) => empty($data[$field] ?? null)
        );

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'account_details' => ['بيانات الحساب (اسم البنك/المحفظة، اسم صاحب الحساب، رقم الحساب) مطلوبة لهذا النوع من وسائل الدفع.'],
            ]);
        }
    }

    private function assertCurrencyConsistent(PaymentMethodType $type, mixed $currency): void
    {
        if ($type->requiresCurrency() && empty($currency)) {
            throw ValidationException::withMessages([
                'currency' => ['يجب تحديد عملة هذا الحساب (ILS أو USD).'],
            ]);
        }

        if (! $type->requiresCurrency() && ! empty($currency)) {
            throw ValidationException::withMessages([
                'currency' => ['وسيلة الدفع النقدية (Cash) لا تُربط بعملة ثابتة.'],
            ]);
        }
    }
}
