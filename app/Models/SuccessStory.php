<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SuccessStory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'display_order',
        'status',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($story) {
            if (is_null($story->display_order)) {
                $story->display_order = 0;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('success_story_before_image')->singleFile();
        $this->addMediaCollection('success_story_after_image')->singleFile();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
