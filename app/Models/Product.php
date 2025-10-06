<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'affiliate_link',
        'price',
        'productcategory_id',
        'featured',
        'status',
        'discount_active',
        'discount_price',
    ];

    protected $casts = [
        'productcategory_id'  => 'integer',
        'price' => 'double',
        'discount_price' => 'double',
        'discount_active' => 'boolean',
    ];

    protected $appends = [
        'final_price',
        'discount_percent',
    ];

    public function productcategory()
    {
        return $this->belongsTo(ProductCategory::class, 'productcategory_id', 'id');
    }

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
                    ->generateSlugsFrom('title')
                    ->saveSlugsTo('slug')
                    ->doNotGenerateSlugsOnUpdate();
    }

    public function favouriteProducts()
    {
        return $this->hasMany(UserFavouriteProduct::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getFinalPriceAttribute()
    {
        $price = $this->price ?? 0;
        $discountPrice = $this->discount_price;

        if ($this->discount_active && !is_null($discountPrice) && $discountPrice > 0 && $discountPrice < $price) {
            return (float) $discountPrice;
        }

        return (float) $price;
    }

    public function getDiscountPercentAttribute()
    {
        $price = $this->price ?? 0;
        $finalPrice = $this->final_price;

        if ($price > 0 && $finalPrice < $price) {
            return round((($price - $finalPrice) / $price) * 100, 2);
        }

        return 0;
    }
}
