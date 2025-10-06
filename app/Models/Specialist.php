<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'specialty',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedules()
    {
        return $this->hasMany(SpecialistSchedule::class);
    }

    public function appointments()
    {
        return $this->hasMany(SpecialistAppointment::class);
    }

    public function freeRequests()
    {
        return $this->hasMany(FreeBookingRequest::class);
    }

    public function users()
    {
        return $this->hasMany(UserProfile::class);
    }
}
