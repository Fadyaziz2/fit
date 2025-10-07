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
            'notes'         => $this->notes,
            'user_id'       => $this->user_id,
            'specialist_id' => $this->specialist_id,
            'free_booking_used_at' => $this->free_booking_used_at,
            'specialist'    => $this->whenLoaded('specialist', function () {
                return [
                    'id' => $this->specialist?->id,
                    'name' => $this->specialist?->name,
                    'phone' => $this->specialist?->phone,
                    'specialty' => $this->specialist?->specialty,
                    'branch_id' => $this->specialist?->branch_id,
                    'branches' => $this->specialist?->branches->map(function ($branch) {
                        return [
                            'id' => $branch->id,
                            'name' => $branch->name,
                        ];
                    })->values(),
                ];
            }),
            'bmi'           => $this->bmi,
            'bmr'           => $this->bmr,
            'ideal_weight'  => $this->ideal_weight,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
