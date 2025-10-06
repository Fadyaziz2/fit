<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExclusiveOfferResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
            'status' => $this->status,
            'image' => getSingleMedia($this, 'exclusive_offer_image', false),
            'activated_at' => $this->activated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
