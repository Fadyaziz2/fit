<?php

namespace App\Services;

use App\Models\DiscountCode;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiscountCodeService
{
    /**
     * Prepare a discount summary for the given cart items.
     *
     * @return array{code: \App\Models\DiscountCode, subtotal: float, discount: float, payable: float}
     * @throws ValidationException
     */
    public function buildSummary(string $code, int $userId, Collection $cartItems): array
    {
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_required'),
            ]);
        }

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'code' => __('message.order_checkout_requires_cart'),
            ]);
        }

        $subtotal = $this->calculateCartSubtotal($cartItems);

        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_requires_amount'),
            ]);
        }

        $discountCode = DiscountCode::whereRaw('LOWER(code) = ?', [mb_strtolower($normalizedCode)])
            ->first();

        if (!$discountCode) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_not_found'),
            ]);
        }

        $this->ensureCanUse($discountCode, $userId);

        $discountAmount = $discountCode->calculateDiscountAmount($subtotal);

        if ($discountAmount <= 0) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_not_applicable'),
            ]);
        }

        $payable = round(max($subtotal - $discountAmount, 0), 2);

        return [
            'code' => $discountCode,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'payable' => $payable,
        ];
    }

    /**
     * Distribute a discount amount across the provided cart items.
     *
     * @return float[] indexed sequentially matching the provided collection order.
     */
    public function distributeDiscount(Collection $cartItems, float $discountAmount): array
    {
        $items = $cartItems->values();
        $count = $items->count();

        if ($discountAmount <= 0 || $count === 0) {
            return array_fill(0, $count, 0.0);
        }

        $subtotal = $this->calculateCartSubtotal($items);

        if ($subtotal <= 0) {
            return array_fill(0, $count, 0.0);
        }

        $allocations = [];
        $applied = 0.0;

        foreach ($items as $index => $item) {
            $lineSubtotal = $this->resolveLineSubtotal($item);

            if ($lineSubtotal <= 0) {
                $allocations[$index] = 0.0;
                continue;
            }

            if ($index === $count - 1) {
                $lineDiscount = round($discountAmount - $applied, 2);
            } else {
                $ratio = $lineSubtotal / $subtotal;
                $lineDiscount = round($discountAmount * $ratio, 2);
                $applied += $lineDiscount;
            }

            $lineDiscount = max(0.0, min($lineDiscount, $lineSubtotal));
            $allocations[$index] = $lineDiscount;
        }

        return $allocations;
    }

    /**
     * Ensure a discount code can be used by the given user.
     *
     * @throws ValidationException
     */
    public function ensureCanUse(DiscountCode $discountCode, int $userId): void
    {
        if (!$discountCode->is_active) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_inactive'),
            ]);
        }

        if ($discountCode->starts_at && $discountCode->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_not_started'),
            ]);
        }

        if ($discountCode->expires_at && $discountCode->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_expired'),
            ]);
        }

        if ($discountCode->max_redemptions !== null && $discountCode->redemption_count >= $discountCode->max_redemptions) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_max_redemptions'),
            ]);
        }

        if ($discountCode->is_one_time_per_user && $discountCode->userHasRedeemed($userId)) {
            throw ValidationException::withMessages([
                'code' => __('message.discount_code_already_used'),
            ]);
        }
    }

    protected function calculateCartSubtotal(Collection $cartItems): float
    {
        return round($cartItems->sum(function ($item) {
            return $this->resolveLineSubtotal($item);
        }), 2);
    }

    protected function resolveLineSubtotal($item): float
    {
        $lineSubtotal = (float) ($item->total_price ?? 0);

        if ($lineSubtotal <= 0 && property_exists($item, 'unit_price')) {
            $quantity = property_exists($item, 'quantity') ? (int) ($item->quantity ?? 1) : 1;
            $lineSubtotal = (float) ($item->unit_price ?? 0) * max($quantity, 1);
        }

        return round(max($lineSubtotal, 0), 2);
    }
}
