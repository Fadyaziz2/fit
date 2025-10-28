<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        abort_if($request->user()->user_type === 'user', 403);

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where('user_type', 'user'),
            ],
        ]);

        /** @var User $user */
        $user = User::findOrFail($data['user_id']);

        $thread = $this->chat->getOrCreateThreadForUser($user);
        $wasRecentlyCreated = $thread->wasRecentlyCreated;

        $updates = ['last_admin_read_at' => now()];

        if (!$thread->assigned_to) {
            $updates['assigned_to'] = $request->user()->id;
        }

        $thread->forceFill($updates);
        $thread->save();

        $thread->loadMissing(['user:id,first_name,last_name,display_name,email', 'assignedTo:id,first_name,last_name,display_name']);
        $thread->setRelation('messages', collect());

        $resource = new ChatThreadResource($thread);

        return $resource
            ->response()
            ->setStatusCode($wasRecentlyCreated ? 201 : 200);
    }

    public function searchUsers(Request $request)
    {
        abort_if($request->user()->user_type === 'user', 403);

        $search = trim((string) $request->input('search'));

        $query = User::query()
            ->where('user_type', 'user');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('display_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'display_name', 'email']);

        $data = $users->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->display_name ?? trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
            ];
        });

        return response()->json(['data' => $data]);
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
