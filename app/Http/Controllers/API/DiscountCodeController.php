<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\DiscountCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountCodeController extends Controller
{
    public function __construct(protected DiscountCodeService $discountService)
    {
    }

    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();

        $cartItems = CartItem::where('user_id', $userId)
            ->orderBy('id')
            ->get();

        if ($cartItems->isEmpty()) {
            return json_custom_response([
                'message' => __('message.order_checkout_requires_cart'),
            ], 400);
        }

        try {
            $summary = $this->discountService->buildSummary($request->input('code'), $userId, $cartItems);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = $exception->validator?->errors()->first() ?? __('message.discount_code_invalid');

            return json_custom_response(['message' => $message], 422);
        }

        $response = [
            'message' => __('message.discount_code_applied'),
            'data' => [
                'code' => $summary['code']->code,
                'name' => $summary['code']->name,
                'discount_type' => $summary['code']->discount_type,
                'discount_value' => (float) $summary['code']->discount_value,
                'is_one_time_per_user' => (bool) $summary['code']->is_one_time_per_user,
                'max_redemptions' => $summary['code']->max_redemptions,
                'remaining_redemptions' => $summary['code']->remainingRedemptions(),
                'subtotal_amount' => $summary['subtotal'],
                'discount_amount' => $summary['discount'],
                'payable_amount' => $summary['payable'],
            ],
        ];

        return json_custom_response($response);
    }
}
