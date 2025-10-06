<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecialistAppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->appointment_date,
            'time' => substr($this->appointment_time, 0, 5),
            'status' => $this->status,
            'type' => $this->type,
            'notes' => $this->notes,
            'admin_comment' => $this->admin_comment,
            'specialist' => $this->whenLoaded('specialist', function () {
                return [
                    'id' => $this->specialist?->id,
                    'name' => $this->specialist?->name,
                    'phone' => $this->specialist?->phone,
                    'specialty' => $this->specialist?->specialty,
                    'branch' => $this->specialist?->branch ? [
                        'id' => $this->specialist?->branch?->id,
                        'name' => $this->specialist?->branch?->name,
                    ] : null,
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch?->id,
                    'name' => $this->branch?->name,
                    'address' => $this->branch?->address,
                ];
            }),
        ];
    }
}
