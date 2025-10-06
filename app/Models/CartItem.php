<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_discount',
        'total_price',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'double',
        'unit_discount' => 'double',
        'total_price' => 'double',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
