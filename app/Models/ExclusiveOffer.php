<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExclusiveOffer extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'button_text',
        'button_url',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    protected $appends = ['offer_image'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getOfferImageAttribute(): ?string
    {
        return getSingleMedia($this, 'exclusive_offer_image', false);
    }
}
