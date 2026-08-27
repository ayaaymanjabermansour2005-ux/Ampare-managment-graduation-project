<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasAttachments, HasFactory, SoftDeletes;

    public const COMPLAINABLE_TYPES = [
        'invoice' => Invoice::class,
        'generator' => Generator::class,
        'fault' => Fault::class,
        'user' => User::class,
        'subscription' => Subscription::class,
        'payment' => Payment::class,
    ];

    public static function statusValues(): array
    {
        return array_column(ComplaintStatus::cases(), 'value');
    }

    protected $fillable = [
        'submitted_by',
        'complainable_type',
        'complainable_id',
        'conversation_id',
        'subject',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'status' => ComplaintStatus::class,
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function complainable(): MorphTo
    {
        return $this->morphTo();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return class-string|null null إذا كانت القيمة غير موجودة بالخريطة
     */
    public static function resolveComplainableClass(?string $typeKey): ?string
    {
        if (! $typeKey) {
            return null;
        }

        return self::COMPLAINABLE_TYPES[$typeKey] ?? null;
    }

    public static function labelForComplainableClass(?string $class): ?string
    {
        if (! $class) {
            return null;
        }

        $key = array_search($class, self::COMPLAINABLE_TYPES, strict: true);

        return $key !== false ? $key : null;
    }

    protected function complainableTypeLabel(): Attribute
    {
        return Attribute::get(
            fn () => self::labelForComplainableClass($this->complainable_type)
        );
    }

    public function relatedOwnerId(): ?int
    {
        return match ($this->complainable_type) {
            Generator::class => $this->complainable?->owner_id,
            Fault::class => $this->complainable?->generator?->owner_id,
            Subscription::class => $this->complainable?->generator?->owner_id,
            Invoice::class => $this->complainable?->subscription?->generator?->owner_id,
            Payment::class => $this->complainable?->invoice?->subscription?->generator?->owner_id,
            default => null,
        };
    }
}
