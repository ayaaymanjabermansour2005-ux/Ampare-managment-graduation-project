<?php

namespace App\Actions\Complaint;

use App\DTOs\Complaint\CreateComplaintData;
use App\Enums\ComplaintStatus;
use App\Events\ComplaintSubmitted;
use App\Models\Complaint;
use App\Models\User;

final class CreateComplaintAction
{
    public function execute(CreateComplaintData $data, User $user): Complaint
    {
        $class = Complaint::resolveComplainableClass($data->complainableType);

        $complaint = Complaint::create([
            'submitted_by' => $user->id,
            'complainable_type' => $class,
            'complainable_id' => $class ? $data->complainableId : null,
            'subject' => $data->subject,
            'description' => $data->description,
            'channel' => $data->channel,
            'priority' => $data->priority,
            'status' => ComplaintStatus::Pending,
        ]);

        $complaint = $complaint->fresh(['submitter', 'complainable']);

        ComplaintSubmitted::dispatch($complaint);

        return $complaint;
    }
}
