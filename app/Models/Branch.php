<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

    public function specialists()
    {
        return $this->hasMany(Specialist::class);
    }

    public function appointments()
    {
        return $this->hasMany(SpecialistAppointment::class);
    }

    public function freeBookingRequests()
    {
        return $this->hasMany(FreeBookingRequest::class);
    }
}
