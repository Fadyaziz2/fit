<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'last_message_at',
        'last_admin_read_at',
        'last_user_read_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_admin_read_at' => 'datetime',
        'last_user_read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->user_type === 'user') {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }
}
