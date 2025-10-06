<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExclusiveOfferResource;
use App\Models\ExclusiveOffer;

class ExclusiveOfferController extends Controller
{
    public function show()
    {
        $offer = ExclusiveOffer::active()
            ->orderByDesc('activated_at')
            ->orderByDesc('updated_at')
            ->first();

        if (!$offer) {
            return json_custom_response([
                'data' => null,
            ]);
        }

        return json_custom_response([
            'data' => new ExclusiveOfferResource($offer),
        ]);
    }
}
