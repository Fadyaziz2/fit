<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing(['sender:id,first_name,last_name,display_name']);
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat.thread.' . $this->message->thread_id);
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        $profileImage = null;
        if ($sender && method_exists($sender, 'getFirstMediaUrl')) {
            $profileImage = $sender->getFirstMediaUrl('profile_image');
        }

        return [
            'id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'message' => $this->message->message,
            'read_at' => optional($this->message->read_at)?->toIso8601String(),
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $sender?->id,
                'name' => $sender?->display_name ?? trim(($sender?->first_name . ' ' . $sender?->last_name) ?? ''),
                'profile_image' => $profileImage,
            ],
        ];
    }
}
