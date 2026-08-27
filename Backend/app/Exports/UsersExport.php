<?php

namespace App\Exports;

use App\Enums\Role;
use App\Models\User;
use App\Support\ExportLabel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected ?string $roleFilter = null,
        protected ?string $search = null,
        protected ?string $status = null,
        protected ?string $subscriptionStatus = null,
    ) {}

    public function query(): Builder
    {
        $query = User::query()->with('roles');

        if ($this->subscriptionStatus === 'active') {
            $query->whereHas(
                'subscriber.subscriptions',
                fn ($q) => $q->where('subscriptions.status', 'active')
            );
        } elseif ($this->subscriptionStatus === 'none') {
            $query->whereDoesntHave(
                'subscriber.subscriptions',
                fn ($q) => $q->where('subscriptions.status', 'active')
            );
        } elseif ($this->subscriptionStatus === 'locked') {
            $query->whereNotNull('locked_until')->where('locked_until', '>', now());
        }

        return $query
            ->when($this->roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->roleFilter)))
            ->when($this->search, fn ($q) => $q->where(function (Builder $sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }

    public function headings(): array
    {
        return [
            ExportLabel::heading('id'),
            ExportLabel::heading('name'),
            ExportLabel::heading('email'),
            ExportLabel::heading('phone'),
            ExportLabel::heading('role'),
            ExportLabel::heading('status'),
            ExportLabel::heading('joined_at'),
        ];
    }

    public function map($user): array
    {
        $roleName = $user->roles->first()?->name;

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $roleName ? ExportLabel::forEnum(Role::from($roleName)) : '',
            ExportLabel::forEnum($user->status),
            $user->created_at?->toDateString(),
        ];
    }
}
