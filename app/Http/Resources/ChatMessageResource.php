<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $sender = $this->whenLoaded('sender');

        $profileImage = null;
        if ($sender && method_exists($sender, 'getFirstMediaUrl')) {
            $profileImage = $sender->getFirstMediaUrl('profile_image');
        }

        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'sender_id' => $this->sender_id,
            'sender_type' => $this->sender_type,
            'message' => $this->message,
            'read_at' => optional($this->read_at)?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'sender' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->display_name ?? trim(($sender->first_name . ' ' . $sender->last_name)),
                'profile_image' => $profileImage,
            ] : null,
        ];
    }
}
