<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diet;
use App\Http\Resources\DietResource;
use App\Http\Resources\DietDetailResource;
use App\Models\UserFavouriteDiet;

class DietController extends Controller
{
    public function getList(Request $request)
    {
        $diet = Diet::query();

        $diet->when(request('title'), function ($q) {
            return $q->where('title', 'LIKE', '%' . request('title') . '%');
        });
        // if( $request->has('is_premium') && isset($request->is_premium) ) {
        //     $diet = $diet->where('is_premium',request('is_premium'));
        // }

        if( $request->has('is_featured') && isset($request->is_featured) ) {
            $diet = $diet->where('is_featured',request('is_featured'));
        }

        if( $request->has('is_premium') && isset($request->is_premium) ) {
            $diet = $diet->where('is_premium', request('is_premium'));
        }

        // $diet->when(request('is_featured'), function ($q) {
        //     return $q->where('is_featured',request('is_featured'));
        // });
        
        $diet->when(request('categorydiet_id'), function ($q) {
            return $q->where('categorydiet_id', request('categorydiet_id'));
        });

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $diet->count();
            }
        }

        $diet = $diet->orderBy('title', 'asc')->paginate($per_page);

        $items = DietResource::collection($diet);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function getDetail(Request $request)
    {
        $diet = Diet::where('id',request('id'))->first();
           
        if( $diet == null )
        {
            return json_message_response( __('message.not_found_entry',['name' => __('message.diet') ]) );
        }

        $diet_data = new DietDetailResource($diet);
            $response = [
                'data' => $diet_data,
            ];
             
        return json_custom_response($response);
        
    }

    public function getUserFavouriteDiet(Request $request)
    {
        $diet = Diet::myDiet();

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)) {
            if(is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ) {
                $per_page = $diet->count();
            }
        }

        $diet = $diet->orderBy('title', 'asc')->paginate($per_page);

        $items = DietResource::collection($diet);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function userFavouriteDiet(Request $request)
    {
        $user_id = auth()->id();
        $diet_id = $request->diet_id;

        $diet = Diet::where('id', $diet_id )->first();
        if( $diet == null )
        {
            return json_message_response( __('message.not_found_entry',['name' => __('message.diet') ]) );
        }
        $user_favourite_diet = UserFavouriteDiet::where('user_id', $user_id)->where('diet_id',$diet_id)->first();
        
        if($user_favourite_diet != null) {
            $user_favourite_diet->delete();
            $message = __('message.unfavourite_diet_list');
        } else {
            $data = [
                'user_id'   => $user_id,
                'diet_id'   => $diet_id,
            ];
            
            UserFavouriteDiet::create($data);
            $message = __('message.favourite_diet_list');
        }

        return json_message_response($message);
    }
}
