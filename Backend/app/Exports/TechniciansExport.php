<?php

namespace App\Exports;

use App\Models\Technician;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TechniciansExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected User $user,
        protected ?string $search = null,
    ) {}

    public function query(): Builder
    {
        $query = Technician::query()
            ->with(['user', 'owner'])
            ->withCount('tasks');

        if (! $this->user->isAdmin()) {
            $query->where('owner_id', $this->user->id);
        }

        return $query
            ->when($this->search, fn ($q) => $q->whereHas(
                'user',
                fn ($userQuery) => $userQuery->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('technician_id'),
            ExportLabel::heading('name'),
            ExportLabel::heading('email'),
            ExportLabel::heading('phone'),
            ExportLabel::heading('owner'),
            ExportLabel::heading('status'),
            ExportLabel::heading('assigned_tasks_count'),
            ExportLabel::heading('joined_at'),
        ];
    }

    public function map($technician): array
    {
        return [
            $technician->id,
            $technician->user?->name,
            $technician->user?->email,
            $technician->user?->phone,
            $technician->owner?->name,
            ExportLabel::forEnum($technician->status),
            $technician->tasks_count,
            $technician->created_at?->toDateString(),
        ];
    }
}
