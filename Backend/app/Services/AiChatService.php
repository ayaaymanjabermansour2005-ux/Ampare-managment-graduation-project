<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AiChatService
{
    public function availableGeneratorsFor(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Generator::query()->with('owner')->get();
        }

        if ($user->isOwner()) {
            return Generator::where('owner_id', $user->id)->with('owner')->get();
        }

        if ($user->isSubscriber()) {
            $subscriber = $user->subscriber;

            if (! $subscriber) {
                return collect();
            }

            return Generator::query()
                ->whereHas(
                    'subscriptions',
                    fn ($q) => $q->whereHas(
                        'subscriberMeter.subscriber',
                        fn ($q2) => $q2->where('id', $subscriber->id)
                    )
                )
                ->with('owner')
                ->get();
        }

        if ($user->isTechnician()) {
            $technician = $user->technician;

            if (! $technician) {
                return collect();
            }

            // FIX: كانت بترجّع كل مولدات صاحب العمل (owner_id) بغض النظر عن
            // الربط الفعلي بجدول generator_technician — يعني الفني كان يشوف
            // مولدات مش مرتبط فيها بقائمة "بدء محادثة جديدة"، وبعدين لما
            // يختارها بيترفض الطلب (GeneratorPolicy::isLinkedTechnician بتفحص
            // pivot فعلي مش owner_id). لازم نفس منطق الربط هون تمامًا.
            return $technician->generators()->with('owner')->get();
        }

        return collect();
    }

    public function listFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = AiChatSession::query()->with('generator')->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($perPage);
    }
}
