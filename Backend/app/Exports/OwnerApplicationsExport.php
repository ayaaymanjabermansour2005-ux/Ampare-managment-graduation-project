<?php

namespace App\Exports;

use App\Models\OwnerApplication;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OwnerApplicationsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected ?string $search = null,
        protected ?string $status = null,
        protected ?string $sort = null,
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
    ) {}

    public function query(): Builder
    {
        $query = OwnerApplication::query()
            ->with('reviewedBy')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate));

        match ($this->sort) {
            'created_asc' => $query->oldest(),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query;
    }

    public function headings(): array
    {
        return [
            'الاسم',
            'الإيميل',
            'رقم الهاتف',
            'اسم المولد المطلوب',
            'الحالة',
            'تاريخ التقديم',
            'روجع بواسطة',
            'تاريخ المراجعة',
            'سبب الرفض',
        ];
    }

    public function map($application): array
    {
        return [
            $application->name,
            $application->email,
            $application->phone,
            $application->generator_name,
            $application->status->label(),
            $application->created_at?->toDateTimeString(),
            $application->reviewedBy?->name,
            $application->reviewed_at?->toDateTimeString(),
            $application->review_note,
        ];
    }
}
