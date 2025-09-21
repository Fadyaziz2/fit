<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExercise extends Model
{
    use HasFactory;

    protected $fillable = [ 'user_id', 'workout_id', 'exercise_id' ];
    
    protected $casts = [
        'user_id'      => 'integer',
        'exercise_id'   => 'integer',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id', 'id');
    }
    public function workout()
    {
        return $this->belongsTo(Workout::class, 'workout_id', 'id');
    }
}
