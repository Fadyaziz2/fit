<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'phone',
        'status',
        'specialist_id',
        'appointment_id',
        'admin_notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'specialist_id' => 'integer',
        'appointment_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }

    public function appointment()
    {
        return $this->belongsTo(SpecialistAppointment::class);
    }
}
