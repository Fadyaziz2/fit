<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_text' => $this->button_text,
            'redirect_url' => $this->redirect_url,
            'display_order' => $this->display_order,
            'status' => $this->status,
            'banner_image' => getSingleMedia($this, 'banner_image', null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
