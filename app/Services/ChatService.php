<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function getOrCreateThreadForUser(User $user): ChatThread
    {
        return ChatThread::firstOrCreate(
            ['user_id' => $user->id],
            ['last_user_read_at' => now()]
        );
    }

    public function listThreadsForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return ChatThread::query()
            ->with(['user:id,first_name,last_name,display_name,email', 'assignedTo:id,first_name,last_name,display_name'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->whereNull('read_at')->where('sender_type', 'user');
            }])
            ->orderByRaw('COALESCE(last_message_at, updated_at, created_at) DESC')
            ->paginate($perPage);
    }

    public function fetchThreadMessages(ChatThread $thread, ?string $before, int $limit = 50): Collection
    {
        $query = $thread->messages()
            ->with('sender:id,first_name,last_name,display_name')
            ->orderByDesc('created_at');

        if ($before) {
            $query->where('created_at', '<', Carbon::parse($before));
        }

        return $query->limit($limit)->get()->sortBy('created_at')->values();
    }

    public function markThreadAsRead(ChatThread $thread, string $role): void
    {
        $now = now();

        if ($role === 'user') {
            $thread->update(['last_user_read_at' => $now]);
            $thread->messages()
                ->whereNull('read_at')
                ->where('sender_type', 'admin')
                ->update(['read_at' => $now]);
        } else {
            $thread->update(['last_admin_read_at' => $now]);
            $thread->messages()
                ->whereNull('read_at')
                ->where('sender_type', 'user')
                ->update(['read_at' => $now]);
        }
    }

    public function sendMessage(User $sender, ?ChatThread $thread, string $content): ChatMessage
    {
        $role = $sender->user_type === 'user' ? 'user' : 'admin';

        if ($role === 'user') {
            $thread = $this->getOrCreateThreadForUser($sender);
        }

        if (!$thread) {
            throw ValidationException::withMessages([
                'thread_id' => __('The chat thread is required.'),
            ]);
        }

        if ($role === 'user' && $thread->user_id !== $sender->id) {
            abort(403);
        }

        $message = new ChatMessage([
            'message' => $content,
            'sender_type' => $role,
        ]);

        $message->sender()->associate($sender);
        $message->thread()->associate($thread);
        $message->save();

        $now = now();

        $thread->forceFill([
            'last_message_at' => $now,
            $role === 'user' ? 'last_user_read_at' : 'last_admin_read_at' => $now,
        ]);

        if ($role === 'admin' && !$thread->assigned_to) {
            $thread->assigned_to = $sender->id;
        }

        $thread->save();

        $message->load('sender:id,first_name,last_name,display_name');

        broadcast(new ChatMessageSent($message))->toOthers();

        return $message;
    }
}
