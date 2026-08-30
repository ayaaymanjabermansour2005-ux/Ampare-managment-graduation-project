<?php

namespace App\Http\Requests\Conversation;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:'.$this->user()?->id],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'يجب تحديد الطرف الآخر بالمحادثة.',
            'user_id.different' => 'لا يمكن بدء محادثة مع نفسك.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->filled('user_id')) {
                return;
            }

            $currentUser = $this->user();
            $targetUser = User::find($this->input('user_id'));

            if (! $targetUser) {
                return;
            }

            if (! $this->usersCanConverse($currentUser, $targetUser)) {
                $validator->errors()->add('user_id', 'لا يمكن بدء محادثة مع هذا المستخدم — لا توجد علاقة عمل تربطكما.');
            }
        });
    }

    private function usersCanConverse(User $a, User $b): bool
    {
        if ($a->isAdmin() || $b->isAdmin()) {
            return true;
        }

        [$otherParty, $ownerUser] = $a->isOwner() ? [$b, $a] : [$a, $b];

        if (! $ownerUser->isOwner()) {
            return false;
        }

        if ($otherParty->isSubscriber()) {
            return $this->subscriberRelatedToOwner($otherParty, $ownerUser);
        }

        if ($otherParty->isTechnician()) {
            return $this->technicianRelatedToOwner($otherParty, $ownerUser);
        }

        return false;
    }

    private function subscriberRelatedToOwner(User $subscriberUser, User $ownerUser): bool
    {
        $subscriber = $subscriberUser->subscriber;

        if (! $subscriber) {
            return false;
        }

        return $subscriber->subscriptions()
            ->whereHas('generator', fn ($g) => $g->where('owner_id', $ownerUser->id))
            ->exists();
    }

    private function technicianRelatedToOwner(User $technicianUser, User $ownerUser): bool
    {
        $technician = $technicianUser->technician;

        if (! $technician) {
            return false;
        }

        if ($technician->owner_id === $ownerUser->id) {
            return true;
        }

        return $technician->generators()
            ->where('generators.owner_id', $ownerUser->id)
            ->exists();
    }
}
