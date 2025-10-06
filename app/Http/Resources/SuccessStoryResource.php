<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SuccessStoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'display_order' => $this->display_order,
            'status' => $this->status,
            'before_image' => getSingleMedia($this, 'success_story_before_image', null),
            'after_image' => getSingleMedia($this, 'success_story_after_image', null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
