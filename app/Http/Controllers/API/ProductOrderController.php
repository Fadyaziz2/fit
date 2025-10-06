<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOrderResource;
use App\Models\CartItem;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductOrderController extends Controller
{
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
        ]);

        if ($validator->fails()) {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();

        $cartItems = CartItem::with('product')
            ->where('user_id', $userId)
            ->get();

        if ($cartItems->isEmpty()) {
            return json_message_response(__('message.order_checkout_requires_cart'), 400);
        }

        $orderIds = [];

        DB::transaction(function () use ($cartItems, $request, $userId, &$orderIds) {
            foreach ($cartItems as $item) {
                $totalPrice = $item->total_price ?? ($item->unit_price ?? 0) * ($item->quantity ?? 1);

                $order = ProductOrder::create([
                    'product_id' => $item->product_id,
                    'user_id' => $userId,
                    'quantity' => $item->quantity,
                    'status' => 'placed',
                    'unit_price' => $item->unit_price ?? 0,
                    'total_price' => $totalPrice,
                    'payment_method' => 'cash_on_delivery',
                    'customer_name' => $request->input('full_name'),
                    'customer_phone' => $request->input('phone'),
                    'shipping_address' => $request->input('address'),
                    'customer_note' => $request->input('note'),
                ]);

                $orderIds[] = $order->id;
            }

            CartItem::where('user_id', $userId)->delete();
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
