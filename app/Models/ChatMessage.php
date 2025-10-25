<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'sender_type',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function markAsReadFor(string $role): void
    {
        if ($this->read_at) {
            return;
        }

        if ($role === 'user' && $this->sender_type === 'admin') {
            $this->forceFill(['read_at' => now()])->save();
        }

        if ($role !== 'user' && $this->sender_type === 'user') {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
