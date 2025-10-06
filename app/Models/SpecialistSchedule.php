<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialistSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration',
    ];

    protected $casts = [
        'specialist_id' => 'integer',
        'day_of_week' => 'integer',
        'slot_duration' => 'integer',
    ];

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }
}
