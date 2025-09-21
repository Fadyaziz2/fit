<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
            'id'            => $this->id,
            'age'           => $this->age,
            'height'        => $this->height,
            'height_unit'   => $this->height_unit,
            'weight'        => $this->weight,
            'weight_unit'   => $this->weight_unit,
            'address'       => $this->address,
            'user_id'       => $this->user_id,
            'bmi'           => $this->bmi,
            'bmr'           => $this->bmr,
            'ideal_weight'  => $this->ideal_weight,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
