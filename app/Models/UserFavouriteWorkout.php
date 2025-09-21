<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavouriteWorkout extends Model
{
    use HasFactory;

    protected $fillable = [ 'user_id', 'workout_id' ];
    
    protected $casts = [
        'user_id'      => 'integer',
        'workout_id'   => 'integer',
    ];
}
