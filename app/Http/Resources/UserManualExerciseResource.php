<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserManualExerciseResource extends JsonResource
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
            'activity' => $this->activity,
            'duration' => $this->duration,
            'performed_on' => optional($this->performed_on)->format('Y-m-d'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
