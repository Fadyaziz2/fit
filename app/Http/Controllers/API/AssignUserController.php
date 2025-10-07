<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AssignDiet;
use App\Models\Diet;
use App\Models\Workout;
use App\Models\AssignWorkout;
use App\Http\Resources\DietResource;
use App\Http\Resources\WorkoutResource;

class AssignUserController extends Controller
{
    public function getAssignDiet(Request $request)
    {
        $userId = auth()->id();

        $assign_diet = Diet::myAssignDiet($userId)
            ->with(['userAssignDiet' => function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                }
            }]);
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $assign_diet->count();
            }
        }

        $assign_diet = $assign_diet->orderBy('id', 'desc')->paginate($per_page);

        $data = collect($assign_diet->items())
            ->map(fn ($diet) => (new DietResource($diet))->toArray($request))
            ->values()
            ->all();

        $response = [
            'pagination'    => json_pagination_response($assign_diet),
            'data'          => $data,
        ];

        return json_custom_response($response);
    }

    public function getAssignWorkout(Request $request)
    {
        $assign_workout = Workout::myAssignWorkout();
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $assign_workout->count();
            }
        }

        $assign_workout = $assign_workout->orderBy('id', 'desc')->paginate($per_page);

        $data = collect($assign_workout->items())
            ->map(fn ($workout) => (new WorkoutResource($workout))->toArray($request))
            ->values()
            ->all();

        $response = [
            'pagination'    => json_pagination_response($assign_workout),
            'data'          => $data,
        ];

        return json_custom_response($response);
    }

}
