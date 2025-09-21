<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AssignDiet extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['user_id','diet_id','serve_times','custom_plan'];

    protected $casts = [
        'user_id'     => 'integer',
        'diet_id'     => 'integer',
        'serve_times' => 'array',
        'custom_plan' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function diet()
    {
        return $this->belongsTo(Diet::class, 'diet_id', 'id');
    }
}
