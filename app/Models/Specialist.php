<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialist extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'super_user_id',
        'name',
        'phone',
        'email',
        'specialty',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'super_user_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_specialist')->withTimestamps();
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

    public function superUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_user_id');
    }
}
