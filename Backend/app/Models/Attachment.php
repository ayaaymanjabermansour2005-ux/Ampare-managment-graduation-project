<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasAttachments, HasFactory, SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'uploaded_by',
        'document_type',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'extension',
        'mime_type',
        'file_size',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'document_type' => DocumentType::class,

    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeUploadedBy(Builder $query, int $userId): Builder
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeOfType(Builder $query, string $documentType): Builder
    {
        return $query->where('document_type', $documentType);
    }
}
