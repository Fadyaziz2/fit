<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;


class Workout extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use HasSlug;

    protected $fillable = [ 'title', 'slug', 'description', 'status', 'workout_type_id', 'level_id', 'is_premium' ];

    protected $casts = [
        'workout_type_id'   => 'integer',
        'level_id'          => 'integer',
        'is_premium'        => 'integer',
    ];

    public function workouttype()
    {
        return $this->belongsTo(WorkoutType::class, 'workout_type_id', 'id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'id');
    }


    public function workoutDayExercise()
    {
        return $this->hasMany(WorkoutDayExercise::class, 'workout_id', 'id');
    }

    public function workoutDay()
    {
        return $this->hasMany(WorkoutDay::class, 'workout_id', 'id');
    }
    
    public function userFavouriteWorkout()
    {
        return $this->hasMany(UserFavouriteWorkout::class, 'workout_id', 'id');
    }
    public function userAssignWorkout()
    {
        return $this->hasMany(AssignWorkout::class, 'workout_id', 'id');
    }

    public function scopeMyWorkout($query, $user_id =null)
    {
        $user = auth()->user();

        if($user->hasRole(['user'])){
            $query = $query->whereHas('userFavouriteWorkout', function ($q) use($user) {
                $q->where('user_id', $user->id);
            });
        }

        if($user_id != null) {
            return $query->whereHas('userAssignWorkout', function ($q) use($user_id) {
                $q->where('user_id', $user_id);
            });
             
        }

        return $query;
    }
    public function scopeMyAssignWorkout($query, $user_id =null)
    {
        $user = auth()->user();

        if($user->hasRole(['user'])){
            $query = $query->whereHas('userAssignWorkout', function ($q) use($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public function workoutExercise() {
        return $this->hasManyThrough(
            WorkoutDayExercise::class,
            WorkoutDay::class,
        );
    }

    public function getIsFavoriteAttribute()
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $this->userFavouriteWorkout()
            ->where('user_id', $user->id)
            ->exists();
    }
    
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
                    ->generateSlugsFrom('title')
                    ->saveSlugsTo('slug')
                    ->doNotGenerateSlugsOnUpdate();
    }
}
