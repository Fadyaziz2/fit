<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
//use App\Models\Product;

class ProductDetailResource  extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                       => $this->id,
            'title'                    => $this->title,
            'description'              => $this->description,
            'affiliate_link'           => $this->affiliate_link,
            'price'                    => $this->price,
            'final_price'              => $this->final_price,
            'discount_price'           => $this->discount_price,
            'discount_active'          => (bool) $this->discount_active,
            'discount_percent'         => $this->discount_percent,
            'product_image'            => getSingleMedia($this, 'product_image', null),
            'productcategory_id'       => $this->productcategory_id,
            'featured'                 => $this->featured,
            'status'                   => $this->status,
            'is_favourite'             => $this->when(auth()->check(), $this->isFavourite()),
            'is_in_cart'               => $this->when(auth()->check(), $this->isInCart()),
            'cart_quantity'            => $this->when(auth()->check(), $this->cartQuantity()),
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }

    protected function isFavourite(): bool
    {
        if ($this->relationLoaded('favouriteProducts')) {
            return $this->favouriteProducts->isNotEmpty();
        }

        return false;
    }

    protected function isInCart(): bool
    {
        if ($this->relationLoaded('cartItems')) {
            return $this->cartItems->isNotEmpty();
        }

        return false;
    }

    protected function cartQuantity(): int
    {
        if ($this->relationLoaded('cartItems')) {
            return optional($this->cartItems->first())->quantity ?? 0;
        }

        return 0;
    }
}