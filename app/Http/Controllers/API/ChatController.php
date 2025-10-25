<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\ChatThread;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chat)
    {
    }

    public function index(Request $request)
    {
        $authUser = $request->user();

        if ($authUser->user_type === 'user') {
            $thread = $this->chat->getOrCreateThreadForUser($authUser);
            $thread->loadMissing(['user:id,first_name,last_name,display_name,email']);

            $messages = $this->chat->fetchThreadMessages($thread, null, $request->integer('limit', 50));

            $this->chat->markThreadAsRead($thread, 'user');

            return new ChatThreadResource($thread->setRelation('messages', $messages));
        }

        $threads = $this->chat->listThreadsForAdmin($request->integer('per_page', 15));

        return ChatThreadResource::collection($threads);
    }

    public function show(Request $request, ChatThread $thread)
    {
        $authUser = $request->user();

        if ($authUser->user_type === 'user' && $thread->user_id !== $authUser->id) {
            abort(403);
        }

        $thread->loadMissing(['user:id,first_name,last_name,display_name,email', 'assignedTo:id,first_name,last_name,display_name']);

        $messages = $this->chat->fetchThreadMessages(
            $thread,
            $request->filled('before') ? Carbon::parse($request->input('before'))->toIso8601String() : null,
            $request->integer('limit', 50)
        );

        $role = $authUser->user_type === 'user' ? 'user' : 'admin';

        $this->chat->markThreadAsRead($thread, $role);

        return new ChatThreadResource($thread->setRelation('messages', $messages));
    }

    public function sendToSelf(Request $request)
    {
        $message = $this->chat->sendMessage($request->user(), null, $request->validate([
            'message' => ['required', 'string'],
        ])['message']);

        return new ChatMessageResource($message);
    }

    public function sendToThread(Request $request, ChatThread $thread)
    {
        $message = $this->chat->sendMessage($request->user(), $thread, $request->validate([
            'message' => ['required', 'string'],
        ])['message']);

        return new ChatMessageResource($message);
    }
}
