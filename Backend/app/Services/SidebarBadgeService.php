<?php

namespace App\Services;

use App\Enums\ArticleCommentStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContactMessageStatus;
use App\Enums\FaultStatus;
use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OwnerApplicationStatus;
use App\Enums\PaymentStatus;
use App\Models\ArticleComment;
use App\Models\Complaint;
use App\Models\ContactMessage;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\OwnerApplication;
use App\Models\Payment;
use App\Models\User;

class SidebarBadgeService
{
    public function counts(): array
    {
        return [
            'owner_applications_pending' => OwnerApplication::where('status', OwnerApplicationStatus::Pending->value)->count(),

            'payments_pending' => Payment::whereIn('status', [
                PaymentStatus::Pending->value,
                PaymentStatus::NeedsCorrection->value,
            ])->count(),

            'complaints_open' => Complaint::whereIn('status', [
                ComplaintStatus::Pending->value,
                ComplaintStatus::InProgress->value,
            ])->count(),

            'faults_pending' => Fault::where('status', FaultStatus::PendingVerification->value)->count(),

            'contact_messages_new' => ContactMessage::where('status', ContactMessageStatus::New->value)->count(),

            'generators_pending_verification' => Generator::where(
                'status',
                GeneratorStatus::PendingVerification->value
            )->count(),

            'users_locked' => User::whereNotNull('locked_until')->where('locked_until', '>', now())->count(),

            'invoices_overdue' => Invoice::where('status', InvoiceStatus::Overdue->value)->count(),

            'article_comments_pending' => ArticleComment::where('status', ArticleCommentStatus::Pending->value)->count(),
        ];
    }
}
