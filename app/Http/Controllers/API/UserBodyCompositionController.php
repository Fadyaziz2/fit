<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserBodyCompositionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBodyCompositionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $compositions = $user->bodyCompositions()->get();

        return json_custom_response([
            'data' => UserBodyCompositionResource::collection($compositions),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'recorded_at' => ['required', 'date'],
            'fat_weight' => ['nullable', 'numeric', 'min:0'],
            'water_weight' => ['nullable', 'numeric', 'min:0'],
            'muscle_weight' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'recorded_at' => __('message.body_composition_date'),
            'fat_weight' => __('message.fat_weight'),
            'water_weight' => __('message.water_weight'),
            'muscle_weight' => __('message.muscle_weight'),
        ]);

        $validated = $validator->validate();

        $composition = $user->bodyCompositions()->updateOrCreate(
            ['recorded_at' => $validated['recorded_at']],
            [
                'fat_weight' => $validated['fat_weight'],
                'water_weight' => $validated['water_weight'],
                'muscle_weight' => $validated['muscle_weight'],
            ]
        );

        return json_custom_response([
            'message' => __('message.body_composition_saved'),
            'data' => new UserBodyCompositionResource($composition),
        ]);
    }
}
