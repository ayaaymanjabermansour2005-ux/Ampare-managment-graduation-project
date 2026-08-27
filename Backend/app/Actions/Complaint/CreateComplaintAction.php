<?php

namespace App\Actions\Complaint;

use App\DTOs\Complaint\CreateComplaintData;
use App\Enums\ComplaintStatus;
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
            'status' => ComplaintStatus::Pending,
        ]);

        return $complaint->fresh(['submitter', 'complainable']);
    }
}
