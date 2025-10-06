<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'quantity',
        'status',
        'unit_price',
        'total_price',
        'payment_method',
        'status_comment',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'customer_note',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    /**
     * Get the product that belongs to the order.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that placed the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
