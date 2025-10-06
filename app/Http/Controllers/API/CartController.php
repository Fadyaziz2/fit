<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Product;
use App\Http\Resources\CartItemResource;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function getList(Request $request)
    {
        $userId = auth()->id();

        $cartItems = CartItem::where('user_id', $userId)
            ->with(['product' => function ($query) use ($userId) {
                $query->when($userId, function ($q) use ($userId) {
                    $q->with([
                        'favouriteProducts' => function ($inner) use ($userId) {
                            $inner->where('user_id', $userId);
                        },
                        'cartItems' => function ($inner) use ($userId) {
                            $inner->where('user_id', $userId);
                        },
                    ]);
                });
            }])
            ->orderBy('id', 'desc')
            ->get();

        $items = CartItemResource::collection($cartItems);

        $response = [
            'data' => $items,
            'summary' => [
                'total_items' => (int) $cartItems->sum('quantity'),
                'total_amount' => round($cartItems->sum('total_price'), 2),
            ],
        ];

        return json_custom_response($response);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();
        $product = Product::where('id', $request->product_id)->where('status', 'active')->first();

        if (!$product) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.product')]));
        }

        $quantity = $request->quantity ?? 1;
        $quantity = $quantity < 1 ? 1 : $quantity;

        $unitPrice = $product->final_price;
        $unitDiscount = max(0, ($product->price ?? 0) - $unitPrice);

        $cartItem = CartItem::where('user_id', $userId)->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->quantity = $cartItem->quantity + $quantity;
            $cartItem->unit_price = $unitPrice;
            $cartItem->unit_discount = $unitDiscount;
            $cartItem->total_price = $cartItem->quantity * $unitPrice;
            $cartItem->save();

            $message = __('message.cart_updated_successfully');
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_discount' => $unitDiscount,
                'total_price' => $unitPrice * $quantity,
            ]);

            $message = __('message.cart_added_successfully');
        }

        return json_message_response($message);
    }

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();

        $cartItem = CartItem::where('user_id', $userId)->where('product_id', $request->product_id)->first();

        if (!$cartItem) {
            return json_message_response(__('message.cart_item_not_found'));
        }

        $cartItem->delete();

        return json_message_response(__('message.cart_removed_successfully'));
    }
}
