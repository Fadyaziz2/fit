<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManualExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity',
        'duration',
        'performed_on',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'duration' => 'float',
        'performed_on' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
