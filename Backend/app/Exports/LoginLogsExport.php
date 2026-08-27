<?php

namespace App\Exports;

use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Activitylog\Models\Activity;

/**
 * تصدير سجل تسجيل الدخول — أدمن فقط (نفس تصريح LoginLogController::index
 * اللي بيعمل abort_unless(isAdmin())). ما في "تعدد أدوار" هون لأن الراوت
 * أصلًا مسجَّل تحت Route::middleware('role:admin') بملف routes/api/v1.php.
 */
class LoginLogsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
        protected ?string $event = null,
    ) {}

    public function query(): Builder
    {
        if (! $this->user->isAdmin()) {
            return Activity::query()->whereRaw('1 = 0');
        }

        return Activity::query()
            ->when(
                in_array($this->event, ['login_succeeded', 'login_failed'], true),
                fn ($query) => $query->where('description', $this->event),
                fn ($query) => $query->whereIn('description', ['login_succeeded', 'login_failed'])
            )
            ->when($this->search, fn ($query) => $query->whereHasMorph(
                'subject',
                [User::class],
                fn ($subQuery) => $subQuery->where('name', 'like', "%{$this->search}%")
            ))
            ->with('subject')
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('log_id'),
            ExportLabel::heading('event'),
            ExportLabel::heading('user'),
            ExportLabel::heading('ip'),
            ExportLabel::heading('date'),
        ];
    }

    public function map($log): array
    {
        $eventKey = $log->description === 'login_failed' ? 'login_failed' : 'login_succeeded';

        return [
            $log->id,
            __('exports.values.event_'.$eventKey),
            $log->subject?->name ?? __('exports.values.unknown_user'),
            $log->properties['ip'] ?? '-',
            $log->created_at?->toDateTimeString(),
        ];
    }
}
