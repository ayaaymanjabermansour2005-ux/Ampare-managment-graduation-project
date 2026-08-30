<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OwnerMonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $owner,
        public string $pdfBinary,
        public Carbon $month,
    ) {}

    public function build(): static
    {
        return $this->subject('تقريرك الشهري — '.$this->month->translatedFormat('F Y'))
            ->view('emails.owner-monthly-report')
            ->with(['owner' => $this->owner, 'month' => $this->month])
            ->attachData($this->pdfBinary, 'monthly-report-'.$this->month->format('Y-m').'.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
