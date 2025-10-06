<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function getList(Request $request)
    {
        $query = Banner::query()->active()->orderBy('display_order')->orderByDesc('created_at');

        $banners = $query->get();

        return json_custom_response([
            'data' => BannerResource::collection($banners),
        ]);
    }
}
