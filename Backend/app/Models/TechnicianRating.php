<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianRating extends Model
{
    use HasFactory;

    protected $fillable = ['technician_task_id', 'technician_id', 'rated_by', 'rating', 'comment'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TechnicianTask::class, 'technician_task_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}
