<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\ChatThread;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatThreadController extends Controller
{
    public function __construct(protected ChatService $chat)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_if($request->user()->user_type === 'user', 403);
        $pusher = [
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'enabled' => config('broadcasting.default') === 'pusher',
        ];

        return view('chat.index', [
            'pusher' => $pusher,
            'authUser' => $request->user(),
        ]);
    }

    public function threads(Request $request)
    {
        abort_if($request->user()->user_type === 'user', 403);
        $threads = $this->chat->listThreadsForAdmin($request->integer('per_page', 25));

        return ChatThreadResource::collection($threads);
    }

    public function show(Request $request, ChatThread $thread)
    {
        abort_if($request->user()->user_type === 'user', 403);
        $thread->load(['user:id,first_name,last_name,display_name,email', 'assignedTo:id,first_name,last_name,display_name']);

        $messages = $this->chat->fetchThreadMessages(
            $thread,
            $request->input('before'),
            $request->integer('limit', 50)
        );

        $this->chat->markThreadAsRead($thread, 'admin');

        return new ChatThreadResource($thread->setRelation('messages', $messages));
    }

    public function send(Request $request, ChatThread $thread)
    {
        abort_if($request->user()->user_type === 'user', 403);
        $message = $this->chat->sendMessage(
            $request->user(),
            $thread,
            $request->validate(['message' => ['required', 'string']])['message']
        );

        return new ChatMessageResource($message);
    }
}
