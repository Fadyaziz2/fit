<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialistAppointment extends Model
{
    use HasFactory;

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
