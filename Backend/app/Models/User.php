<?php

namespace App\Models;

use App\Enums\CommissionMode;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens,
        HasFactory,
        HasRoles,
        LogsActivity,
        MustVerifyEmailTrait,
        Notifiable,
        SoftDeletes;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'birth_date',
        'address',
        'latitude',
        'longitude',
        'bio',
        'whatsapp',
        'facebook_url',
        'instagram_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'locked_until' => 'datetime',
            'last_locked_at' => 'datetime',
            'birth_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'commission_mode' => CommissionMode::class,
            'commission_rate' => 'decimal:2',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN->value);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(Role::GENERATOR_OWNER->value);
    }

    public function isSubscriber(): bool
    {
        return $this->hasRole(Role::SUBSCRIBER->value);
    }

    public function isTechnician(): bool
    {
        return $this->hasRole(Role::TECHNICIAN->value);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function platformCommissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class, 'owner_id');
    }

    public function latestCommissionRate(): HasOne
    {
        return $this->hasOne(PlatformCommission::class, 'owner_id')->latestOfMany();
    }

    public function subscriber(): HasOne
    {
        return $this->hasOne(Subscriber::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    public function generators(): HasMany
    {
        return $this->hasMany(Generator::class, 'owner_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class, 'owner_id');
    }

    public function reportedFaults(): HasMany
    {
        return $this->hasMany(Fault::class, 'reported_by');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'owner_id');
    }

    public function conversations(): Builder
    {
        return Conversation::visibleTo($this);
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function employedTechnicians(): HasMany
    {
        return $this->hasMany(Technician::class, 'owner_id');
    }

    public function requestedTechnicianTasks(): HasMany
    {
        return $this->hasMany(TechnicianTask::class, 'requested_by');
    }

    public function assignedTechnicianTasks(): HasMany
    {
        return $this->hasMany(TechnicianTask::class, 'assigned_by');
    }

    public function reviewedTechnicianTasks(): HasMany
    {
        return $this->hasMany(TechnicianTask::class, 'reviewed_by');
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
