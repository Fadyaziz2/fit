<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBodyComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recorded_at',
        'fat_weight',
        'water_weight',
        'muscle_weight',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'recorded_at' => 'date',
        'fat_weight' => 'float',
        'water_weight' => 'float',
        'muscle_weight' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
