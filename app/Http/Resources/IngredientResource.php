<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'protein' => $this->protein,
            'fat' => $this->fat,
            'carbs' => $this->carbs,
            'image' => getSingleMedia($this, 'ingredient_image', null),
        ];
    }
}
