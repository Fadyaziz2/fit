<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\WorkoutResource;
use App\Http\Resources\DietResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CartItemResource;
use Illuminate\Support\Collection;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $favouriteWorkouts = collect();
        if ($this->relationLoaded('userFavouriteWorkout')) {
            $favouriteWorkouts = $this->userFavouriteWorkout
                ->filter(function ($item) {
                    return optional($item)->workout !== null;
                })
                ->map(function ($item) {
                    return $item->workout;
                });
        }

        $favouriteDiets = collect();
        if ($this->relationLoaded('userFavouriteDiet')) {
            $favouriteDiets = $this->userFavouriteDiet
                ->filter(function ($item) {
                    return optional($item)->diet !== null;
                })
                ->map(function ($item) {
                    return $item->diet;
                });
        }

        $favouriteProducts = collect();
        if ($this->relationLoaded('userFavouriteProducts')) {
            $favouriteProducts = $this->userFavouriteProducts
                ->filter(function ($item) {
                    return optional($item)->product !== null;
                })
                ->map(function ($item) {
                    return $item->product;
                });
        }

        /** @var Collection $cartItems */
        $cartItems = collect();
        if ($this->relationLoaded('cartItems')) {
            $cartItems = $this->cartItems;
        }

        $cartItemCount = $cartItems->sum('quantity');
        $cartTotal = round($cartItems->sum('total_price'), 2);

        return [
            'id'                => $this->id,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'display_name'      => $this->display_name,
            'email'             => $this->email,
            'username'          => $this->username,
            'gender'            => $this->gender,
            'status'            => $this->status,
            'user_type'         => $this->user_type,
            'phone_number'      => $this->phone_number,
            'player_id'         => $this->player_id,
            'profile_image'     => getSingleMedia($this, 'profile_image',null),
            'login_type'        => $this->login_type,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'user_profile'      => isset($this->userProfile) ? new UserProfileResource($this->userProfile) : null,
            'is_subscribe'      => $this->is_subscribe,
            'favourite_workouts'=> WorkoutResource::collection($favouriteWorkouts),
            'favourite_diets'   => DietResource::collection($favouriteDiets),
            'favourite_products'=> ProductResource::collection($favouriteProducts),
            'cart_items'        => CartItemResource::collection($cartItems),
            'cart_item_count'   => (int) $cartItemCount,
            'cart_total_amount' => $cartTotal,
        ];
    }
}
