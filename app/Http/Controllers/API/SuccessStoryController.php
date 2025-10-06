<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessStoryResource;
use App\Models\SuccessStory;
use Illuminate\Http\Request;

class SuccessStoryController extends Controller
{
    public function getList(Request $request)
    {
        $stories = SuccessStory::query()
            ->active()
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return json_custom_response([
            'data' => SuccessStoryResource::collection($stories),
        ]);
    }
}
