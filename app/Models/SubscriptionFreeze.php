<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionFreeze extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'freeze_start_date',
        'freeze_end_date',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'user_id' => 'integer',
        'freeze_start_date' => 'datetime',
        'freeze_end_date' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
