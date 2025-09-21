<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WorkoutDay extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;
    
    protected $fillable = [ 'workout_id', 'sequence', 'is_rest'];

    protected $casts = [
        'is_rest'       => 'integer',
        'workout_id'    => 'integer',
        'sequence'      => 'integer',
    ];
    
    public function workout()
    {
        return $this->belongsTo(Workout::class, 'workout_id', 'id');
    }

    public function workoutDayExercise()
    {
        return $this->hasMany(WorkoutDayExercise::class, 'workout_day_id', 'id');
    }

    protected static function boot() {
        parent::boot();
        static::deleted(function ($row) {
            $row->workoutDayExercise()->delete();
        });
    }

}