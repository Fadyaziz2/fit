<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialistAppointment extends Model
{
    use HasFactory;

    public const STATUS_OPTIONS = [
        'confirmed' => 'message.appointment_statuses.confirmed',
        'pending' => 'message.appointment_statuses.pending',
        'rescheduled' => 'message.appointment_statuses.rescheduled',
        'cancelled' => 'message.appointment_statuses.cancelled',
        'wrong_number' => 'message.appointment_statuses.wrong_number',
        'not_subscribed' => 'message.appointment_statuses.not_subscribed',
        'subscribed' => 'message.appointment_statuses.subscribed',
        'other' => 'message.appointment_statuses.other',
    ];

    public const BLOCKING_STATUSES = [
        'pending',
        'confirmed',
        'rescheduled',
        'subscribed',
    ];

    protected $fillable = [
        'user_id',
        'specialist_id',
        'branch_id',
        'appointment_date',
        'appointment_time',
        'type',
        'status',
        'notes',
        'admin_comment',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'specialist_id' => 'integer',
        'branch_id' => 'integer',
        'appointment_date' => 'date',
    ];

    public static function statusValidationRule(): string
    {
        return 'in:' . implode(',', array_keys(self::STATUS_OPTIONS));
    }

    public static function statusLabels(): array
    {
        return collect(self::STATUS_OPTIONS)
            ->map(fn ($translationKey) => __($translationKey))
            ->all();
    }

    public static function statusBadgeClasses(): array
    {
        return [
            'confirmed' => 'bg-success',
            'pending' => 'bg-warning text-dark',
            'rescheduled' => 'bg-info text-dark',
            'cancelled' => 'bg-danger',
            'wrong_number' => 'bg-secondary',
            'not_subscribed' => 'bg-secondary',
            'subscribed' => 'bg-primary',
            'other' => 'bg-dark',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function freeRequest()
    {
        return $this->hasOne(FreeBookingRequest::class, 'appointment_id');
    }
}
