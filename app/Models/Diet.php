<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Diet extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use HasSlug;

    protected $fillable = [ 'title', 'slug', 'categorydiet_id', 'calories', 'carbs', 'protein', 'fat', 'servings', 'days', 'total_time', 'is_featured', 'status', 'ingredients', 'description', 'is_premium' ];

    protected $casts = [
        'is_premium'        => 'integer',
        'categorydiet_id'   => 'integer',
        'servings'          => 'integer',
        'days'              => 'integer',
        'ingredients'       => 'array',
    ];

    public function categorydiet()
    {
        return $this->belongsTo(CategoryDiet::class, 'categorydiet_id', 'id');
    }

    public function userFavouriteDiet()
    {
        return $this->hasMany(UserFavouriteDiet::class, 'diet_id', 'id');
    }

    public function userAssignDiet()
    {
        return $this->hasMany(AssignDiet::class, 'diet_id', 'id');
    }

    public function scopeMyDiet($query, $user_id =null)
    {
        $user = auth()->user();

        if($user->hasRole(['user'])){
            $query = $query->whereHas('userFavouriteDiet', function ($q) use($user) {
                $q->where('user_id', $user->id);
            });
        }

        if($user_id != null) {
            return $query->whereHas('userAssignDiet', function ($q) use($user_id) {
                $q->where('user_id', $user_id);
            });
             
        }

        return $query;
    }

    public function scopeMyAssignDiet($query, $user_id =null)
    {
        $user = auth()->user();
        if($user->hasRole(['user'])){
            $query = $query->whereHas('userAssignDiet', function ($q) use($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    protected static function boot() {
        parent::boot();
        static::deleted(function ($row) {
            $row->userFavouriteDiet()->delete();
        });
    }

    public function getIsFavoriteAttribute()
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $this->userFavouriteDiet()
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
