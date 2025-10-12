<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    use HasFactory;

   protected $fillable = [ 'user_id', 'package_id', 'total_amount', 'payment_type', 'txn_id', 'transaction_detail', 'payment_status', 'subscription_start_date', 'subscription_end_date', 'package_data', 'status', 'callback' ];

    protected $casts = [
        'user_id'  => 'integer',
        'package_id'  => 'integer',
        'total_amount' => 'double',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }

    public function freezes(): HasMany
    {
        return $this->hasMany(SubscriptionFreeze::class);
    }

    public function activeFreeze(): HasOne
    {
        return $this->hasOne(SubscriptionFreeze::class)
            ->where('status', SubscriptionFreeze::STATUS_ACTIVE)
            ->latestOfMany('freeze_start_date');
    }

    public function scheduledFreezes(): HasMany
    {
        return $this->hasMany(SubscriptionFreeze::class)
            ->where('status', SubscriptionFreeze::STATUS_SCHEDULED)
            ->orderBy('freeze_start_date');
    }

    public function getPackageDataAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    public function setPackageDataAttribute($value)
    {
        $this->attributes['package_data'] = isset($value) ? json_encode($value) : null;
    }

    public function getTransactionDetailAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    public function setTransactionDetailAttribute($value)
    {
        $this->attributes['transaction_detail'] = isset($value) ? json_encode($value) : null;
    }

    public function scopeMySubscription($query, $user_id =null)
    {
        $user = auth()->user();

        if($user->hasRole(['user'])) {
            $query = $query->where('user_id', $user->id);
        }
        return $query;
    }
}
