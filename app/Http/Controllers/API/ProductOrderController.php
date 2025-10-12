<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOrderResource;
use App\Models\CartItem;
use App\Models\DiscountCodeRedemption;
use App\Models\ProductOrder;
use App\Services\DiscountCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductOrderController extends Controller
{
    public function __construct(protected DiscountCodeService $discountService)
    {
    }

    /**
     * Handle checkout for the authenticated user.
     */
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:191',
            'phone' => 'required|string|max:40',
            'address' => 'required|string|max:1000',
            'note' => 'nullable|string|max:1000',
            'discount_code' => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();

        $cartItems = CartItem::with('product')
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get();

        if ($cartItems->isEmpty()) {
            return json_message_response(__('message.order_checkout_requires_cart'), 400);
        }

        $discountCodeInput = $request->input('discount_code');
        $discountSummary = null;
        $discountAllocations = [];

        if ($discountCodeInput) {
            try {
                $discountSummary = $this->discountService->buildSummary($discountCodeInput, $userId, $cartItems);
                $discountAllocations = $this->discountService->distributeDiscount($cartItems, $discountSummary['discount']);
            } catch (\Throwable $exception) {
                $message = __('message.discount_code_invalid');

                if ($exception instanceof \Illuminate\Validation\ValidationException) {
                    $message = $exception->validator?->errors()->first() ?? $message;
                }

                return json_custom_response(['message' => $message], 422);
            }
        }

        $orderIds = [];

        DB::transaction(function () use ($cartItems, $request, $userId, &$orderIds, $discountSummary, $discountAllocations) {
            $discountCode = $discountSummary['code'] ?? null;
            $discountApplied = false;

            foreach ($cartItems->values() as $index => $item) {
                $subtotal = $item->total_price ?? ($item->unit_price ?? 0) * ($item->quantity ?? 1);
                $subtotal = round($subtotal ?? 0, 2);

                $lineDiscount = $discountAllocations[$index] ?? 0;
                $lineDiscount = round(min($lineDiscount, $subtotal), 2);

                $finalTotal = round(max($subtotal - $lineDiscount, 0), 2);

                $order = ProductOrder::create([
                    'product_id' => $item->product_id,
                    'user_id' => $userId,
                    'quantity' => $item->quantity,
                    'status' => 'placed',
                    'unit_price' => $item->unit_price ?? 0,
                    'subtotal_price' => $subtotal,
                    'discount_amount' => $lineDiscount,
                    'discount_code' => $discountCode?->code,
                    'discount_code_id' => $discountCode?->id,
                    'total_price' => $finalTotal,
                    'payment_method' => 'cash_on_delivery',
                    'customer_name' => $request->input('full_name'),
                    'customer_phone' => $request->input('phone'),
                    'shipping_address' => $request->input('address'),
                    'customer_note' => $request->input('note'),
                ]);

                $orderIds[] = $order->id;

                if ($discountCode && $lineDiscount > 0) {
                    DiscountCodeRedemption::create([
                        'discount_code_id' => $discountCode->id,
                        'user_id' => $userId,
                        'product_order_id' => $order->id,
                        'discount_amount' => $lineDiscount,
                        'redeemed_at' => now(),
                    ]);

                    $discountApplied = true;
                }
            }

            CartItem::where('user_id', $userId)->delete();

            if ($discountCode && $discountApplied) {
                $discountCode->increment('redemption_count');
            }
        });

        $orders = ProductOrder::with('product')
            ->whereIn('id', $orderIds)
            ->orderByDesc('id')
            ->get();

        $response = [
            'message' => __('message.order_placed_successfully'),
            'data' => ProductOrderResource::collection($orders),
        ];

        return json_custom_response($response);
    }

    /**
     * Return the authenticated user's order history.
     */
    public function orderHistory(Request $request)
    {
        $userId = auth()->id();

        $orders = ProductOrder::with('product')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        return json_custom_response([
            'data' => ProductOrderResource::collection($orders),
        ]);
    }
}
