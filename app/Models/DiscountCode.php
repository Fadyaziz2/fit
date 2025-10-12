<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'is_active',
        'is_one_time_per_user',
        'max_redemptions',
        'redemption_count',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'is_active' => 'boolean',
        'is_one_time_per_user' => 'boolean',
        'max_redemptions' => 'integer',
        'redemption_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountCodeRedemption::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductOrder::class);
    }

    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($inner) use ($now) {
                $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($inner) use ($now) {
                $inner->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($inner) {
                $inner->whereNull('max_redemptions')
                    ->orWhereColumn('redemption_count', '<', 'max_redemptions');
            });
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    public function remainingRedemptions(): ?int
    {
        if ($this->max_redemptions === null) {
            return null;
        }

        return max(0, $this->max_redemptions - $this->redemption_count);
    }

    public function userHasRedeemed(int $userId): bool
    {
        return $this->redemptions()->where('user_id', $userId)->exists();
    }

    public function calculateDiscountAmount(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $discount = $this->discount_type === 'percentage'
            ? ($amount * ($this->discount_value / 100))
            : $this->discount_value;

        $discount = min($discount, $amount);

        return round(max($discount, 0), 2);
    }
}
