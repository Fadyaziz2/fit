<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserGraph;
use App\Http\Resources\UserGraphResource;
use Carbon\Carbon;

class UserGraphController extends Controller
{
    public function saveGraphData(Request $request)
    {
        $user_id = auth()->id();
        
        $data = $request->all();
        $data['user_id'] = $user_id;

        $result = UserGraph::updateOrCreate(['id' => request('id') ],$data);

        $message = __('message.update_form',[ 'form' => __('message.data') ] );
		
        if( $result->wasRecentlyCreated ) {
			$message = __('message.save_form',[ 'form' => __('message.data') ] );
		}

        return json_custom_response(['message' => $message, 'status' => true]);
    }

    public function getGraphDataList(Request $request)
    {
        $usergraph = UserGraph::myGraph();

        $usergraph->when(request('type'), function ($q) {
            return $q->where('type', request('type'));
        });

        $duration = request('duration');
        $today = Carbon::now();

        $usergraph->when($duration == 'week', function ($q) use($today) {
            $from_date = Carbon::parse($today)->startOfWeek()->toDateTimeString();
            $to_date = Carbon::parse($today)->endOfWeek()->toDateTimeString();
            return $q->whereBetween('date',[ $from_date, $to_date ]);
        });

        $usergraph->when($duration == 'month', function ($q) use($today) {
            return $q->whereMonth('date',$today->month);
        });
        
        $usergraph->when($duration == 'year', function ($q) use($today) {
            return $q->where('date', '>=', $today->subYear());
        });

        $usergraph->when($duration == '3month', function ($q) use($today) { 
            return $q->where('date','>=',Carbon::now()->subMonths(3));
        });

        $orderby = request('orderby') ?? 'desc';
        $per_page = $usergraph->count();
        $usergraph = $usergraph->orderBy('date', $orderby)->paginate($per_page);

        $items = UserGraphResource::collection($usergraph);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function deleteGraphData(Request $request)
    {
        $usergraph = UserGraph::myGraph();

        $usergraph->when(request('id'), function ($q) {
            $id = explode(',', request('id'));
            return $q->whereIn('id', $id);
        });

        $usergraph->delete();
        $message = __('message.delete_form', [ 'form' => __('message.data') ]);
                
        return json_message_response($message);
    }
}