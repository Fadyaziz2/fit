<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\UserFavouriteProduct;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function getList(Request $request)
    {
        $userId = auth()->id();

        $product = Product::where('status', 'active')
            ->when($userId, function ($q) use ($userId) {
                $q->with([
                    'favouriteProducts' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                    'cartItems' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                ]);
            });

        $product->when(request('title'), function ($q) {
            return $q->where('title', 'LIKE', '%' . request('title') . '%');
        });

        $product->when(request('productcategory_id'), function ($q) {
            return $q->where('productcategory_id', request('productcategory_id'));
        });

        if ($request->has('featured')) {
            $featured = strtolower((string) $request->featured);
            if (in_array($featured, ['1', 'true', 'yes'], true)) {
                $product->where('featured', 'yes');
            } elseif (in_array($featured, ['0', 'false', 'no'], true)) {
                $product->where('featured', 'no');
            } else {
                $product->where('featured', $request->featured);
            }
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $product->count();
            }
        }

        $product = $product->orderBy('title', 'asc')->paginate($per_page);

        $items = ProductResource::collection($product);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function getDetail(Request $request)
    {
        $userId = auth()->id();

        $product = Product::where('id',request('id'))
            ->when($userId, function ($q) use ($userId) {
                $q->with([
                    'favouriteProducts' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                    'cartItems' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                ]);
            })
            ->first();

        if( $product == null )
        {
            return json_message_response( __('message.not_found_entry',['name' => __('message.product') ]) );
        }

        $product_data = new ProductDetailResource($product);
            $response = [
                'data' => $product_data,
            ];
             
        return json_custom_response($response);
    }

    public function getUserFavouriteProducts(Request $request)
    {
        $userId = auth()->id();

        $product = Product::where('status', 'active')
            ->whereHas('favouriteProducts', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with([
                'favouriteProducts' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'cartItems' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ]);

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $product->count();
            }
        }

        $product = $product->orderBy('title', 'asc')->paginate($per_page);

        $items = ProductResource::collection($product);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];

        return json_custom_response($response);
    }

    public function toggleFavourite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if($validator->fails())
        {
            return json_custom_response(['message' => $validator->errors()->first()], 422);
        }

        $userId = auth()->id();
        $productId = $request->product_id;

        $product = Product::where('id', $productId)->first();

        if( $product == null )
        {
            return json_message_response( __('message.not_found_entry',['name' => __('message.product') ]) );
        }

        $userFavourite = UserFavouriteProduct::where('user_id', $userId)->where('product_id', $productId)->first();

        if($userFavourite != null) {
            $userFavourite->delete();
            $message = __('message.unfavourite_product_list');
        } else {
            UserFavouriteProduct::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            $message = __('message.favourite_product_list');
        }

        return json_message_response($message);
    }
}
