<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatThreadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');
        $assigned = $this->whenLoaded('assignedTo');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'assigned_to' => $this->assigned_to,
            'last_message_at' => optional($this->last_message_at)?->toIso8601String(),
            'last_admin_read_at' => optional($this->last_admin_read_at)?->toIso8601String(),
            'last_user_read_at' => optional($this->last_user_read_at)?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'unread_count' => $this->when(isset($this->unread_count), $this->unread_count),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->display_name ?? trim(($user->first_name . ' ' . $user->last_name)),
                'email' => $user->email,
            ] : null,
            'assigned_to_user' => $assigned ? [
                'id' => $assigned->id,
                'name' => $assigned->display_name ?? trim(($assigned->first_name . ' ' . $assigned->last_name)),
            ] : null,
            'messages' => ChatMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
