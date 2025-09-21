<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ingredient extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'protein',
        'fat',
        'carbs',
    ];

    protected $casts = [
        'protein' => 'float',
        'fat' => 'float',
        'carbs' => 'float',
    ];
}
