<?php

use App\Models\ChatThread;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.thread.{thread}', function ($user, ChatThread $thread) {
    if ($user->user_type === 'user') {
        return $thread->user_id === $user->id;
    }

    return $user->user_type !== 'user';
});
