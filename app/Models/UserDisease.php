<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDisease extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'started_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'started_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
