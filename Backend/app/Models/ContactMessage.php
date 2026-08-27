<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactMessageSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'subject',
        'message',
        'status',
        'admin_note',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'subject' => ContactMessageSubject::class,
            'status' => ContactMessageStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
