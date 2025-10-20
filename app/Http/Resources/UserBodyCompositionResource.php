<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBodyCompositionResource extends JsonResource
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
            'id' => $this->id,
            'recorded_at' => optional($this->recorded_at)->format('Y-m-d'),
            'recorded_at_formatted' => optional($this->recorded_at)->translatedFormat('F j, Y'),
            'fat_weight' => $this->fat_weight,
            'water_weight' => $this->water_weight,
            'muscle_weight' => $this->muscle_weight,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
