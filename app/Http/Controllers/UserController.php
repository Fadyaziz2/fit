<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use App\Models\User;
use App\Models\Subscription;
use App\Models\AssignDiet;
use App\Models\AssignWorkout;
use App\Models\Diet;
use App\Models\Workout;
use App\Helpers\AuthHelper;
use App\Models\Role;
use App\Http\Requests\UserRequest;
use App\DataTables\SubscriptionDataTable;
use App\Models\UserGraph;
use Carbon\Carbon;
use App\Models\Ingredient;
use InvalidArgumentException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(UsersDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',[ 'form' => __('message.user') ] );
        $auth_user = AuthHelper::authSession();
        if( !$auth_user->can('user-list') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        
        $headerAction = $auth_user->can('user-add') ? '<a href="'.route('users.create').'" class="btn btn-sm btn-primary" role="button">'.__('message.add_form_title', [ 'form' => __('message.user')]).'</a>' : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user', 'assets', 'headerAction'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if( !auth()->user()->can('user-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $assets = ['phone'];
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.user')]);
        $roles = Role::where('status', 1)->where('name', 'user')->get()->pluck('title', 'name');
        return view('users.form', compact('pageTitle','roles','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        if( !auth()->user()->can('user-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $request['password'] = bcrypt($request->password);
        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request['first_name']." ".$request['last_name'];
        $user = User::create($request->all());

        storeMediaFile($user, $request->profile_image, 'profile_image');

        $user->assignRole($request->user_type);

        // Save user Profile data...
        // $user->userProfile()->create($request->userProfile);

        return redirect()->route('users.index')->withSuccess(__('message.save_form',['form' => __('message.user')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SubscriptionDataTable $dataTable,$id)
    {
        if( !auth()->user()->can('user-show') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = User::with('userProfile','roles')->findOrFail($id);

        $subscriptions = Subscription::where('user_id', $id)->get();
    
        $profileImage = getSingleMedia($data, 'profile_image');
        return $dataTable->with('user_id',$id)->render('users.profile', compact('data', 'profileImage','subscriptions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = User::with('userProfile')->findOrFail($id);
        $assets = ['phone'];

        $pageTitle = __('message.update_form_title',[ 'form' => __('message.user')]);
        
        $profileImage = getSingleMedia($data, 'profile_image');
        $roles = Role::where('status', 1)->where('name', 'user')->get()->pluck('title', 'name');

        return view('users.form', compact('data', 'id', 'profileImage', 'pageTitle', 'roles','assets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $user = User::with('userProfile')->findOrFail($id);      
        $request['display_name'] = $request['first_name']." ".$request['last_name'];
        $user->removeRole($user->user_type);
        
        // User user data..
        $user->fill($request->all())->update();

        $user->assignRole($request['user_type']);
        // Save user image...
        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        // user profile data....
        // $user->userProfile->fill($request->userProfile)->update();

        if(auth()->check()){
            return redirect()->route('users.index')->withSuccess(__('message.update_form', ['form' => __('message.user')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form', ['form' => __('message.user')]));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if( !auth()->user()->can('user-delete') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $user = User::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.user')]);

        if($user != '') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.user')]);

        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'datatable_reload' => 'dataTable_wrapper']);
        }

        return redirect()->back()->with($status,$message);

    }

    public function assignDietForm(Request $request)
    {
        $user_id = request('user_id');
        $view = view('users.assign_diet',compact('user_id'))->render();
        return response()->json(['data' =>  $view, 'status'=> true]);
    }

    public function getAssignDietList(Request $request)
    {
        $user_id = request('user_id');
        $data = Diet::myDiet($user_id)
            ->with(['userAssignDiet' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }])
            ->orderBy('id', 'desc')
            ->get();
        $view = view('users.assign-diet-list',compact('user_id', 'data'))->render();
        return response()->json([ 'data' => $view, 'status' => true ]);
    }
 
    public function assignDietSave(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'diet_id' => ['required', 'exists:diets,id'],
        ]);

        $diet = Diet::find($validated['diet_id']);
        $servings = (int) ($diet->servings ?? 0);

        $serveTimes = [];

        if ($servings > 0) {
            $serveTimesInput = $request->validate([
                'serve_times' => ['required', 'array', 'size:' . $servings],
                'serve_times.*' => ['required', 'date_format:H:i'],
            ]);

            $serveTimes = array_values($serveTimesInput['serve_times']);
        }

        AssignDiet::updateOrCreate(
            ['user_id' => $validated['user_id'], 'diet_id' => $validated['diet_id']],
            ['serve_times' => $serveTimes]
        );

        $message = __('message.assigndiet');

        return response()->json(['status' => true, 'type' => 'diet', 'event' => 'norefresh', 'message' => $message]);
    }

    protected function buildDietPlanStructure(Diet $diet): array
    {
        $planData = $diet->ingredients ?? [];

        $planArray = [];

        if (is_array($planData)) {
            foreach ($planData as $dayIndex => $meals) {
                $dayKey = (int) $dayIndex;
                $planArray[$dayKey] = [];

                if (!is_array($meals)) {
                    continue;
                }

                foreach ($meals as $meal) {
                    $planArray[$dayKey][] = $this->normalizeMealSelection($meal);
                }
            }
        }

        ksort($planArray);

        $existingDays = count($planArray);
        $daysCount = (int) ($diet->days ?? 0);
        if ($daysCount < $existingDays) {
            $daysCount = $existingDays;
        }

        $maxMeals = 0;
        foreach ($planArray as $meals) {
            $maxMeals = max($maxMeals, count($meals));
        }

        $servingsCount = (int) ($diet->servings ?? 0);
        if ($servingsCount > $maxMeals) {
            $maxMeals = $servingsCount;
        }

        if ($daysCount === 0) {
            $daysCount = $existingDays;
        }

        $normalizedPlan = [];

        for ($day = 0; $day < $daysCount; $day++) {
            $dayMeals = $planArray[$day] ?? [];
            if ($maxMeals > 0) {
                $dayMeals = array_pad($dayMeals, $maxMeals, []);
            }

            $normalizedPlan[$day] = array_map(function ($meal) {
                return $this->normalizeMealSelection($meal);
            }, $dayMeals);
        }

        if ($daysCount === 0 && empty($normalizedPlan)) {
            $normalizedPlan = [];
        }

        return [
            'plan' => $normalizedPlan,
            'maxMeals' => $maxMeals,
            'days' => $daysCount,
        ];
    }

    public function editAssignDietMeals($userId, $dietId)
    {
        $assignment = AssignDiet::where('user_id', $userId)
            ->where('diet_id', $dietId)
            ->first();

        if (!$assignment) {
            abort(404);
        }

        $diet = Diet::findOrFail($dietId);

        $structure = $this->buildDietPlanStructure($diet);

        $ingredients = Ingredient::orderBy('title')->get();
        $ingredientsMap = $ingredients->keyBy('id');

        $customPlan = $assignment->custom_plan ?? [];

        $view = view('users.edit_assign_diet', [
            'assignment' => $assignment,
            'diet' => $diet,
            'plan' => $structure['plan'],
            'maxMeals' => $structure['maxMeals'],
            'customPlan' => $customPlan,
            'ingredients' => $ingredients,
            'ingredientsMap' => $ingredientsMap,
        ])->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function updateAssignDietMeals(Request $request)
    {
        try {
            $planInput = $this->normalizePlanInput($request->input('plan', []), true);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.invalid_meal_selection'),
            ]);
        }

        $request->merge(['plan' => $planInput]);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'diet_id' => ['required', 'exists:diets,id'],
            'plan' => ['nullable', 'array'],
            'plan.*' => ['nullable', 'array'],
            'plan.*.*' => ['nullable', 'array'],
            'plan.*.*.*' => ['nullable', 'integer'],
        ]);

        $assignment = AssignDiet::where('user_id', $validated['user_id'])
            ->where('diet_id', $validated['diet_id'])
            ->first();

        if (!$assignment) {
            abort(404);
        }

        $diet = Diet::findOrFail($validated['diet_id']);

        $structure = $this->buildDietPlanStructure($diet);
        $normalizedDietPlan = $structure['plan'];

        $selectedPlan = $this->normalizePlanInput($validated['plan'] ?? []);

        $selectedIds = collect($selectedPlan)
            ->flatten()
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value);

        if ($selectedIds->isNotEmpty()) {
            $validIngredientIds = Ingredient::pluck('id')->map(fn ($id) => (int) $id);

            if ($selectedIds->diff($validIngredientIds)->isNotEmpty()) {
                return response()->json([
                    'status' => false,
                    'event' => 'validation',
                    'message' => __('message.invalid_meal_selection'),
                ]);
            }
        }

        $customPlan = [];

        foreach ($normalizedDietPlan as $dayIndex => $dayMeals) {
            $selectedDayMeals = $selectedPlan[$dayIndex] ?? [];

            foreach ($dayMeals as $mealIndex => $defaultIngredients) {
                $defaultSelection = $this->normalizeMealSelection($defaultIngredients);

                if (!array_key_exists($mealIndex, $selectedDayMeals)) {
                    continue;
                }

                $selectedIngredients = $this->normalizeMealSelection($selectedDayMeals[$mealIndex]);

                if ($selectedIngredients === $defaultSelection) {
                    continue;
                }

                $customPlan[$dayIndex][$mealIndex] = $selectedIngredients;
            }

            if (isset($customPlan[$dayIndex]) && empty($customPlan[$dayIndex])) {
                unset($customPlan[$dayIndex]);
            }
        }

        $assignment->custom_plan = !empty($customPlan) ? $customPlan : null;
        $assignment->save();

        $message = __('message.custom_diet_meal_updated');

        return response()->json([
            'status' => true,
            'type' => 'diet',
            'event' => 'norefresh',
            'message' => $message,
        ]);
    }

    protected function normalizePlanInput($plan, bool $strict = false): array
    {
        if (!is_array($plan)) {
            if ($strict && $plan !== null && $plan !== '') {
                throw new InvalidArgumentException('Invalid plan structure.');
            }

            return [];
        }

        $normalized = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            if (!is_array($dayMeals)) {
                if ($strict && $dayMeals !== null && $dayMeals !== '') {
                    throw new InvalidArgumentException('Invalid meal selection.');
                }

                continue;
            }

            $dayKey = (int) $dayIndex;

            foreach ($dayMeals as $mealIndex => $meal) {
                $mealKey = (int) $mealIndex;
                $normalized[$dayKey][$mealKey] = $this->normalizeMealSelection($meal, $strict);
            }
        }

        ksort($normalized);

        foreach ($normalized as $dayKey => $dayMeals) {
            ksort($dayMeals);
            $normalized[$dayKey] = $dayMeals;
        }

        return $normalized;
    }

    protected function normalizeMealSelection($value, bool $strict = false): array
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                if (!is_numeric($item)) {
                    if ($strict) {
                        throw new InvalidArgumentException('Invalid meal ingredient.');
                    }

                    continue;
                }

                $id = (int) $item;

                if ($id <= 0 || in_array($id, $normalized, true)) {
                    continue;
                }

                $normalized[] = $id;
            }

            return $normalized;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (!is_numeric($value)) {
            if ($strict) {
                throw new InvalidArgumentException('Invalid meal ingredient.');
            }

            return [];
        }

        $id = (int) $value;

        return $id > 0 ? [$id] : [];
    }

    public function getAssignWorkoutList(Request $request)
    {
        $user_id = request('user_id');
        $data = Workout::myWorkout($user_id)->orderBy('id', 'desc')->get();
        $view = view('users.assign-workout-list',compact('user_id', 'data'))->render();
        return response()->json([ 'data' => $view, 'status' => true ]);
    }

    public function assignDietDestroy(Request $request)
    {
        $assigndiet = AssignDiet::where('user_id', $request->user_id )->where('diet_id', $request->diet_id )->first();

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.assigndiet')]);
        if($assigndiet != '') {
            $assigndiet->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.assigndiet')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'type' => 'diet', 'event' => 'norefresh']);
        }

        return redirect()->back()->with($status,$message);
    }

    public function assignWorkoutForm(Request $request)
    {
        $user_id = request('user_id');
        $view = view('users.assign_workout',compact('user_id'))->render();
        return response()->json(['data' =>  $view, 'status'=> true]);
    }
    public function assignWorkoutSave(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);
        AssignWorkout::updateOrCreate([ 'user_id' => request('user_id'), 'workout_id' => request('workout_id') ]);
        
        $message = __('message.assignworkout');

        return response()->json(['status' => true,  'type' => 'workout', 'event' => 'norefresh', 'message' => $message]);
    }

    public function assignWorkoutDestroy(Request $request)
    {
        $assignworkout = AssignWorkout::where('user_id', $request->user_id )->where('workout_id', $request->workout_id )->first();

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.assignworkout')]);
        if($assignworkout != '') {
            $assignworkout->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.assignworkout')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'type' => 'workout', 'event' => 'norefresh']);
        }

        return redirect()->back()->with($status,$message);
    }

    public function fetchUserGraphData($type, $unit, $dateValue, $user_id)
    {
  
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $dataArray = [];
        $dateArray = [];

        switch ($dateValue) {
            case 'month':
                $data = UserGraph::whereYear('date', $currentYear)->whereMonth('date', $currentMonth);
                break;
            case 'week':
                $data = UserGraph::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'year':
                $data = UserGraph::whereYear('date', $currentYear);
                break;
            default:
                $data = UserGraph::query();
                break;
        }

        $userWeightValues = $data->where('user_id', $user_id)->where('type', $type)->where('unit', $unit)->orderBy('date', 'asc')->get(['value', 'date']);

        foreach ($userWeightValues as $record) {
            $dataArray[] = $record->value;
            $dateArray[] = $record->date;
        }

        return [
            'data' => $dataArray,
            'category' => $dateArray
        ];
    }

    public function fetchUserGraph(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Unauthorized action.');
        }

        $unit = $request->unit;
        $type = $request->type;
        $dateValue = $request->dateValue;
        $user_id = $request->id;

        $graphData = $this->fetchUserGraphData($type, $unit, $dateValue, $user_id);
        $data = $graphData['data'];
        $category = $graphData['category'];

        return response()->json([
            'data' => $data,
            'category' => $category
        ]);
    }
}
