<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function getList(Request $request)
    {
        $query = Ingredient::query();

        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->input('search') . '%');
        }

        $ingredients = $query->orderBy('title')->get();

        return json_custom_response([
            'data' => IngredientResource::collection($ingredients),
        ]);
    }
}
