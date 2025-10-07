<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\DietDataTable;
use App\Helpers\AuthHelper;
use App\Models\Diet;
use App\Models\AssignDiet;
use App\Models\Ingredient;

use App\Http\Requests\DietRequest;
use App\Support\MealPlan;
use InvalidArgumentException;


class DietController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DietDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.diet')] );
        $auth_user = AuthHelper::authSession();
        if( !$auth_user->can('diet-list') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $assets = ['data-table'];

        $headerAction = $auth_user->can('diet-add') ? '<a href="'.route('diet.create').'" class="btn btn-sm btn-primary" role="button">'.__('message.add_form_title', [ 'form' => __('message.diet')]).'</a>' : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user', 'assets', 'headerAction'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if( !auth()->user()->can('diet-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title',[ 'form' => __('message.diet')]);
        $ingredients = $this->getMealIngredients();

        return view('diet.form', compact('pageTitle', 'ingredients'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DietRequest $request)
    {
        if( !auth()->user()->can('diet-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = $request->except('diet_image');
        $data['ingredients'] = $this->formatMealPlan($request->input('ingredients'));
        $macros = $this->calculateAverageMacros($data['ingredients']);
        $data = array_merge($data, $macros);

        $diet = Diet::create($data);

        storeMediaFile($diet,$request->diet_image, 'diet_image'); 

        return redirect()->route('diet.index')->withSuccess(__('message.save_form', ['form' => __('message.diet')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Diet::findOrFail($id);
    }

    public function servings(Request $request, Diet $diet)
    {
        $userId = $request->input('user_id');

        $serveTimes = [];

        if ($userId) {
            $assignment = AssignDiet::where('diet_id', $diet->id)
                ->where('user_id', $userId)
                ->first();

            if ($assignment && is_array($assignment->serve_times)) {
                $serveTimes = array_values($assignment->serve_times);
            }
        }

        return response()->json([
            'status' => true,
            'servings' => (int) ($diet->servings ?? 0),
            'serve_times' => $serveTimes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if( !auth()->user()->can('diet-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = Diet::findOrFail($id);
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.diet') ]);
        $ingredients = $this->getMealIngredients();

        return view('diet.form', compact('data','id','pageTitle', 'ingredients'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(DietRequest $request, $id)
    {
        if( !auth()->user()->can('diet-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $diet = Diet::findOrFail($id);

        $data = $request->except('diet_image');
        $data['ingredients'] = $this->formatMealPlan($request->input('ingredients'));
        $macros = $this->calculateAverageMacros($data['ingredients']);
        $data = array_merge($data, $macros);

        $diet->update($data);

        // Save diet image...
        if (isset($request->diet_image) && $request->diet_image != null) {
            $diet->clearMediaCollection('diet_image');
            $diet->addMediaFromRequest('diet_image')->toMediaCollection('diet_image');
        }

        if(auth()->check()){
            return redirect()->route('diet.index')->withSuccess(__('message.update_form',['form' => __('message.diet')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form',['form' => __('message.diet') ] ));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if( !auth()->user()->can('diet-delete') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $diet = Diet::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.diet')]);

        if($diet != '') {
            $diet->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.diet')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }

    protected function getMealIngredients(): array
    {
        return Ingredient::orderBy('title')->get()->map(function ($ingredient) {
            $protein = (float) $ingredient->protein;
            $carbs = (float) $ingredient->carbs;
            $fat = (float) $ingredient->fat;
            $calories = round(($protein * 4) + ($carbs * 4) + ($fat * 9), 2);

            return [
                'id' => $ingredient->id,
                'title' => $ingredient->title,
                'protein' => $protein,
                'carbs' => $carbs,
                'fat' => $fat,
                'calories' => $calories,
                'image' => getSingleMedia($ingredient, 'ingredient_image', null),
            ];
        })->toArray();
    }

    protected function formatMealPlan(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        if (isset($decoded['plan']) && is_array($decoded['plan'])) {
            $decoded = $decoded['plan'];
        }

        if (!is_array($decoded)) {
            return [];
        }

        try {
            return MealPlan::normalizePlan($decoded, true);
        } catch (InvalidArgumentException $exception) {
            return [];
        }
    }

    protected function calculateAverageMacros(array $plan): array
    {
        $entries = [];

        foreach ($plan as $dayMeals) {
            if (!is_array($dayMeals)) {
                continue;
            }

            foreach ($dayMeals as $mealEntries) {
                if (!is_array($mealEntries)) {
                    continue;
                }

                foreach ($mealEntries as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }

                    $id = (int) ($entry['id'] ?? 0);
                    $quantity = isset($entry['quantity']) ? (float) $entry['quantity'] : 1.0;

                    if ($id <= 0 || $quantity <= 0) {
                        continue;
                    }

                    $entries[] = [
                        'id' => $id,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        if (empty($entries)) {
            return [
                'protein' => 0,
                'carbs' => 0,
                'fat' => 0,
                'calories' => 0,
            ];
        }

        $ingredientIds = array_unique(array_map(function ($entry) {
            return $entry['id'];
        }, $entries));

        $ingredients = Ingredient::whereIn('id', $ingredientIds)->get(['id', 'protein', 'carbs', 'fat'])->keyBy('id');

        $totals = [
            'protein' => 0.0,
            'carbs' => 0.0,
            'fat' => 0.0,
        ];

        $totalQuantity = 0.0;

        foreach ($entries as $entry) {
            $ingredient = $ingredients->get($entry['id']);

            if (!$ingredient) {
                continue;
            }

            $quantity = (float) $entry['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $totals['protein'] += (float) $ingredient->protein * $quantity;
            $totals['carbs'] += (float) $ingredient->carbs * $quantity;
            $totals['fat'] += (float) $ingredient->fat * $quantity;
            $totalQuantity += $quantity;
        }

        if ($totalQuantity <= 0) {
            return [
                'protein' => 0,
                'carbs' => 0,
                'fat' => 0,
                'calories' => 0,
            ];
        }

        $averages = [
            'protein' => round($totals['protein'] / $totalQuantity, 2),
            'carbs' => round($totals['carbs'] / $totalQuantity, 2),
            'fat' => round($totals['fat'] / $totalQuantity, 2),
        ];

        $averages['calories'] = round(($averages['protein'] * 4) + ($averages['carbs'] * 4) + ($averages['fat'] * 9), 2);

        return $averages;
    }
}
